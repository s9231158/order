<?php
namespace App\ServiceV2;

use App\Factorise;
use App\RepositoryV2\EcpayBackRepositoryV2;
use App\RepositoryV2\EcpayRepositoryV2;
use App\RepositoryV2\OrderInfoRepositoryV2;
use App\RepositoryV2\OrderRepositoryV2;
use App\RepositoryV2\RestaurantRepositoryV2;
use App\RepositoryV2\UserRepositoryV2;
use App\RepositoryV2\UserWalletRepositoryV2;
use App\RepositoryV2\WalletRecordRepositoryV2;
use Illuminate\Support\Str;
use App\CheckMacValueService;
use GuzzleHttp\Client;

class CreateOrderServiceV2
{
    private $RestaurantRepositoryV2;
    private $Factorise;
    private $Restaurant;
    private $OrderRepositoryV2;
    private $OrderInfoRepositoryV2;
    private $UserRepositoryV2;
    private $UserWalletRepositoryV2;
    private $WalletRecordRepositoryV2;
    private $EcpayRepositoryV2;
    private $EcpayBackRepositoryV2;
    public function __construct(EcpayBackRepositoryV2 $EcpayBackRepositoryV2, EcpayRepositoryV2 $EcpayRepositoryV2, WalletRecordRepositoryV2 $WalletRecordRepositoryV2, UserWalletRepositoryV2 $UserWalletRepositoryV2, UserRepositoryV2 $UserRepositoryV2, OrderInfoRepositoryV2 $OrderInfoRepositoryV2, OrderRepositoryV2 $OrderRepositoryV2, Factorise $Factorise, RestaurantRepositoryV2 $RestaurantRepositoryV2)
    {
        $this->Factorise = $Factorise;
        $this->RestaurantRepositoryV2 = $RestaurantRepositoryV2;
        $this->OrderRepositoryV2 = $OrderRepositoryV2;
        $this->OrderInfoRepositoryV2 = $OrderInfoRepositoryV2;
        $this->UserRepositoryV2 = $UserRepositoryV2;
        $this->UserWalletRepositoryV2 = $UserWalletRepositoryV2;
        $this->WalletRecordRepositoryV2 = $WalletRecordRepositoryV2;
        $this->EcpayRepositoryV2 = $EcpayRepositoryV2;
        $this->EcpayBackRepositoryV2 = $EcpayBackRepositoryV2;
    }
    //將餐點一樣id合併
    public function MergeOrdersBySameId(array $Order): array
    {
        $GoodOrder = [];
        $AllOrderMealId = array_column($Order, 'id');
        $MealIdUnique = collect($AllOrderMealId)->unique()->toArray();
        if ($this->CheckSameArray($AllOrderMealId, $MealIdUnique)) {
            foreach ($Order as $Item) {
                $Key = $Item['id'];
                if (array_key_exists($Key, $GoodOrder)) {
                    $GoodOrder[$Key]['price'] += $GoodOrder[$Key]['price'];
                    $GoodOrder[$Key]['quanlity'] += $GoodOrder[$Key]['quanlity'];

                } else {
                    if (isset($Item['description'])) {
                        $GoodOrder[$Key] = [
                            "rid" => $Item['rid'],
                            "id" => $Item['id'],
                            "name" => $Item['name'],
                            "price" => $Item['price'],
                            "quanlity" => $Item['quanlity'],
                            'description' => $Item['description']
                        ];
                    } else {
                        $GoodOrder[$Key] = [
                            "rid" => $Item['rid'],
                            "id" => $Item['id'],
                            "name" => $Item['name'],
                            "price" => $Item['price'],
                            "quanlity" => $Item['quanlity'],
                        ];
                    }
                }
            }
            $GoodOrder = array_values($GoodOrder);
            return $GoodOrder;
        } else {
            return $Order;
        }
    }
    public function CheckRestaurantInDatabase(int $Rid): bool
    {
        return $this->RestaurantRepositoryV2->CheckRestaurantInDatabase($Rid);
    }

    public function CheckSameArray(array $Array1, array $Array2): bool
    {
        if (count($Array1) === count($Array2) && count($Array1) !== 1) {
            return false;
        }
        return true;
    }
    public function CheckTotalPrice(array $Order, int $OrderTotalPrice): bool
    {
        $OrderCollection = collect($Order);
        $RealTotalPrice = $OrderCollection->sum('price');
        if ($RealTotalPrice !== $OrderTotalPrice) {
            return false;
        }
        return true;
    }
    public function CheckRestaurantOpen(int $Rid, string $Date): bool
    {
        return $this->RestaurantRepositoryV2->CheckRestaurantOpen($Rid, $Date);
    }

    public function Menucorrect(int $Rid, array $RequestOrder): bool
    {
        $this->Restaurant = $this->Factorise->Setmenu($Rid);
        return $this->Restaurant->Menucorrect($RequestOrder);
    }

    public function Menuenable(array $ArrayMenuId)
    {
        return $this->Restaurant->Menuenable($ArrayMenuId);
    }

    public function SendApi($OrderInfo, $Order)
    {
        $OrderInfo['uid'] = Str::uuid();
        return $this->Restaurant->SendApi($OrderInfo, $Order);
    }
    public function SaveOrder($Order)
    {
        $UserInfo = $this->UserRepositoryV2->GetUserInfo();
        $UserId = $UserInfo->id;
        $Order['uid'] = $UserId;
        return $this->OrderRepositoryV2->SaveOrder($Order);
    }

    public function SaveOrderInfo($OrderInfo, $Oid)
    {
        $OrderInfoInfo = array_map(function ($Item) use ($Oid) {
            if (isset($Item['description'])) {
                return ['description' => $Item['description'], 'oid' => $Oid, 'name' => $Item['name'], 'price' => $Item['price'], 'quanlity' => $Item['quanlity'], 'created_at' => now(), 'updated_at' => now()];
            }
            return ['description' => null, 'oid' => $Oid, 'name' => $Item['name'], 'price' => $Item['price'], 'quanlity' => $Item['quanlity'], 'created_at' => now(), 'updated_at' => now()];
        }, $OrderInfo);
        return $this->OrderInfoRepositoryV2->SaveOrderInfo($OrderInfoInfo);
    }

    public function DeductMoney($Money)
    {
        $UserInfo = $this->UserRepositoryV2->GetUserInfo();
        $UserId = $UserInfo->id;
        $UserWallet = $this->UserWalletRepositoryV2->GetUserWallet($UserId);
        $Balance = $UserWallet->balance -= $Money;
        $this->UserWalletRepositoryV2->UpdateUserWalletBalance($UserId, $Balance);
    }
    public function AddMoney($Money)
    {
        $UserInfo = $this->UserRepositoryV2->GetUserInfo();
        $UserId = $UserInfo->id;
        $UserWallet = $this->UserWalletRepositoryV2->GetUserWallet($UserId);
        $Balance = $UserWallet->balance += $Money;
        $this->UserWalletRepositoryV2->UpdateUserWalletBalance($UserId, $Balance);
    }

    public function CheckWalletMoney($Money)
    {
        $UserInfo = $this->UserRepositoryV2->GetUserInfo();
        $UserId = $UserInfo->id;
        $UserWallet = $this->UserWalletRepositoryV2->GetUserWallet($UserId);
        $UserBalcane = $UserWallet->balance;
        if ($Money > $UserBalcane) {
            return true;
        }
        return false;
    }
    public function SaveWalletRecord($WalletRecord)
    {
        $UserInfo = $this->UserRepositoryV2->GetUserInfo();
        $UserId = $UserInfo['id'];
        $WalletRecord['uid'] = $UserId;
        $this->WalletRecordRepositoryV2->SaveWalletRecord($WalletRecord);
    }
    public function SaveEcpay($Data)
    {
        $this->EcpayRepositoryV2->SaveEcpay($Data);
        return $Data;
    }
    public function SendEcpayApi($EcpayInfo)
    {
        $Key = env('Ecpay_Key');
        $Iv = env('Ecpay_Iv');
        $UserInfo = $this->UserRepositoryV2->GetUserInfo();
        $UserId = $UserInfo->id;
        if ($EcpayInfo['item_name'] === '加值') {
            $EcpayInfo['trade_desc'] = $UserId . '加值';
        } else {
            $EcpayInfo['trade_desc'] = $UserId . '訂餐';
        }
        $CheckMacValueService = new CheckMacValueService($Key, $Iv);
        $CheckMacValue = $CheckMacValueService->generate($EcpayInfo);
        $EcpayInfo['check_mac_value'] = $CheckMacValue;
        $Client = new Client();
        $Response = $Client->Request('POST', 'http://neil.xincity.xyz:9997/api/Cashier/AioCheckOut', ['json' => $EcpayInfo]);
        $GoodResponse = $Response->getBody();
        $ArrayGoodResponse = json_decode($GoodResponse);
        return [$ArrayGoodResponse, $EcpayInfo];
    }
    public function SaveEcpayBack($EcpayBackInfo)
    {
        $this->EcpayBackRepositoryV2->SaveEcpayCallBack($EcpayBackInfo);
    }
    public function UpdateWalletRecordFail($Uuid)
    {
        return $this->WalletRecordRepositoryV2->FindAndUpdateFailRecord($Uuid);
    }
    public function UpdateOrederFail($Uuid)
    {
        $WalletRecord = $this->WalletRecordRepositoryV2->GetWalletRecord($Uuid);
        $Oid = $WalletRecord[0]['oid'];
        $this->OrderRepositoryV2->FindAndUpdateFailRecord($Oid);
    }
    public function UpdateWalletRecordsuccess($Uuid)
    {
        return $this->WalletRecordRepositoryV2->FindAndUpdatesuccessRecord($Uuid);
    }
    public function UpdateOredersuccess($Uuid)
    {
        $WalletRecord = $this->WalletRecordRepositoryV2->GetWalletRecord($Uuid);
        $Oid = $WalletRecord[0]['oid'];
        $this->OrderRepositoryV2->FindAndUpdatesuccessRecord($Oid);
    }

    public function GetOrder($Oid, $Option)
    {
        $UserInfo = $this->UserRepositoryV2->GetUserInfo();
        $UserId = $UserInfo->id;
        if ($Oid !== null) {
            $Order = $this->OrderRepositoryV2->GetOrder($UserId, $Oid);
            return $Order = $Order->map->only(['id', 'ordertime', 'taketime', 'total', 'status']);
        } else {

            $Order = $this->OrderRepositoryV2->GetOrdersByOffsetLimit($UserId, $Option);
            return $Order = $Order->map->only(['id', 'ordertime', 'taketime', 'total', 'status']);
        }
    }

    public function GetOrderInfo($Oid)
    {
        $UserInfo = $this->UserRepositoryV2->GetUserInfo();
        $UserId = $UserInfo->id;
        $OrderInfo = $this->OrderRepositoryV2->GetOrderInfoJoinOrder($UserId, $Oid);
        return $OrderInfo = $OrderInfo->map->only(['name', 'quanlity', 'price', 'description']);
    }
}