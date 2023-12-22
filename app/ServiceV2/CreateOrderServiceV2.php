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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\CheckMacValueService;
use GuzzleHttp\Client;
use Throwable;

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
    public function __construct(
        EcpayBackRepositoryV2 $EcpayBackRepositoryV2,
        EcpayRepositoryV2 $EcpayRepositoryV2,
        WalletRecordRepositoryV2 $WalletRecordRepositoryV2,
        UserWalletRepositoryV2 $UserWalletRepositoryV2,
        OrderInfoRepositoryV2 $OrderInfoRepositoryV2,
        OrderRepositoryV2 $OrderRepositoryV2,
        UserRepositoryV2 $UserRepositoryV2,
        Factorise $Factorise,
        RestaurantRepositoryV2 $RestaurantRepositoryV2
    ) {
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
    public function MergeOrdersBySameId(array $Order): array
    {
        try {
            $GoodOrder = [];
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
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function CheckRestaurantInDatabase(int $Rid): bool
    {
        try {
            return $this->RestaurantRepositoryV2->CheckRestaurantInDatabase($Rid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function CheckSameArray(array $Array1, array $Array2): bool
    {
        try {
            if (count($Array1) === count($Array2) && count($Array1) !== 1) {
                return false;
            }
            return true;
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function CheckTotalPrice(array $Order, int $OrderTotalPrice): bool
    {
        try {
            $OrderCollection = collect($Order);
            $RealTotalPrice = $OrderCollection->sum('price');
            if ($RealTotalPrice !== $OrderTotalPrice) {
                return false;
            }
            return true;
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function CheckRestaurantOpen(int $Rid, string $Date): bool
    {
        try {
            return $this->RestaurantRepositoryV2->CheckRestaurantOpen($Rid, $Date);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function MenuCorrect(int $Rid, array $RequestOrder): bool
    {
        try {
            $this->Restaurant = $this->Factorise->Setmenu($Rid);
            return $this->Restaurant->MenuCorrect($RequestOrder);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function MenuEnable(array $ArrayMenuId)
    {
        try {
            return $this->Restaurant->MenuEnable($ArrayMenuId);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function SendApi($OrderInfo, $Order)
    {
        try {
            $OrderInfo['uid'] = Str::uuid();
            return $this->Restaurant->SendApi($OrderInfo, $Order);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function SaveOrder($Order)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetUserInfo();
            $UserId = $UserInfo->id;
            $Order['uid'] = $UserId;
            return $this->OrderRepositoryV2->Create($Order);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function SaveOrderInfo($OrderInfo, $Oid)
    {
        try {
            $OrderInfoInfo = array_map(function ($Item) use ($Oid) {
                if (isset($Item['description'])) {
                    return ['description' => $Item['description'], 'oid' => $Oid, 'name' => $Item['name'], 'price' => $Item['price'], 'quanlity' => $Item['quanlity'], 'created_at' => now(), 'updated_at' => now()];
                }
                return ['description' => null, 'oid' => $Oid, 'name' => $Item['name'], 'price' => $Item['price'], 'quanlity' => $Item['quanlity'], 'created_at' => now(), 'updated_at' => now()];
            }, $OrderInfo);
            return $this->OrderInfoRepositoryV2->Create($OrderInfoInfo);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function DeductMoney($Money)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetUserInfo();
            $UserId = $UserInfo->id;
            $UserWallet = $this->UserWalletRepositoryV2->GetOnId($UserId);
            $Balance = $UserWallet->balance -= $Money;
            $this->UserWalletRepositoryV2->UpdateOnId($UserId, $Balance);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function AddMoney($Money, $Option)
    {
        try {
            $Eid = $Option['Eid'];
            $UserId = $this->WalletRecordRepositoryV2->GetUserIdByEidAndTime($Eid, $Option);
            $UserWallet = $this->UserWalletRepositoryV2->GetOnId($UserId[0]['uid']);
            $Balance = $UserWallet->balance += $Money;
            $this->UserWalletRepositoryV2->UpdateOnId($UserId[0]['uid'], $Balance);
        } catch (Throwable $e) {
            Cache::set('AddMoney', 'AddMoney');
        }
    }
    public function CheckWalletMoney($Money)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetUserInfo();
            $UserId = $UserInfo->id;
            $UserWallet = $this->UserWalletRepositoryV2->GetOnId($UserId);
            $UserBalcane = $UserWallet->balance;
            if ($Money > $UserBalcane) {
                return true;
            }
            return false;
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function SaveWalletRecord($WalletRecord)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetUserInfo();
            $UserId = $UserInfo['id'];
            $WalletRecord['uid'] = $UserId;
            $this->WalletRecordRepositoryV2->Create($WalletRecord);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function SendEcpayApi($EcpayInfo)
    {
        try {
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
            $EcpayRestaurantInfo = [
                "merchant_id" => 11,
                "payment_type" => "aio",
                "return_url" => env('Ecpay_ReturnUrl'),
                "encrypt_type" => 1,
                "lang" => "en",
                "choose_payment" => "Credit",
            ];
            $EcpayInfo = array_merge($EcpayInfo, $EcpayRestaurantInfo);
            $CheckMacValue = $CheckMacValueService->generate($EcpayInfo);
            $EcpayInfo['check_mac_value'] = $CheckMacValue;
            $Client = new Client();
            $Response = $Client->Request('POST', env('Ecpay_ApiUrl'), ['json' => $EcpayInfo]);
            $GoodResponse = $Response->getBody();
            $ArrayGoodResponse = json_decode($GoodResponse);
            return [$ArrayGoodResponse, $EcpayInfo];
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function UpdateWalletRecordFail($Uuid)
    {
        try {
            return $this->WalletRecordRepositoryV2->UpdateFailByUid($Uuid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function UpdateOrederFail($Uuid)
    {
        try {
            $WalletRecord = $this->WalletRecordRepositoryV2->GetById($Uuid);
            $Oid = $WalletRecord[0]['oid'];
            $this->OrderRepositoryV2->UpdateFailByOid($Oid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function UpdateWalletRecordSuccess($Uuid)
    {
        try {
            return $this->WalletRecordRepositoryV2->UpdateSuccessById($Uuid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function UpdateOrederSuccess($Uuid)
    {
        try {
            $WalletRecord = $this->WalletRecordRepositoryV2->GetById($Uuid);
            $Oid = $WalletRecord[0]['oid'];
            $this->OrderRepositoryV2->UpdateSuccessByOid($Oid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function GetOrder($Oid, $Option)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetUserInfo();
            $UserId = $UserInfo->id;
            if ($Oid !== null) {
                $Order = $this->OrderRepositoryV2->GetByIdAndOid($UserId, $Oid);
                return $Order = $Order->map->only(['id', 'ordertime', 'taketime', 'total', 'status']);
            } else {
                $Order = $this->OrderRepositoryV2->GetByUidAndOffsetLimit($UserId, $Option);
                return $Order = $Order->map->only(['id', 'ordertime', 'taketime', 'total', 'status']);
            }
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function GetOrderInfo($Oid)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetUserInfo();
            $UserId = $UserInfo->id;
            $OrderInfo = $this->OrderRepositoryV2->GetByUidAndOid($UserId, $Oid);
            return $OrderInfo = $OrderInfo->map->only(['name', 'quanlity', 'price', 'description']);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function GetWalletRecordOnRangeAndType($Option, $Type)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetUserInfo();
            $UserId = $UserInfo->id;
            if ($Type === 'in') {
                $WalletRecord = $this->WalletRecordRepositoryV2->GetByUidAndTypeOnRange($Option, $Type, $UserId);
                $Count = $WalletRecord->count();
                return array('count' => $Count, 'data' => $WalletRecord);
            }
            if ($Type === 'out') {
                $WalletRecord = $this->WalletRecordRepositoryV2->GetByUidAndTypeOnRange($Option, $Type, $UserId);
                $Count = $WalletRecord->count();
                return array('count' => $Count, 'data' => $WalletRecord);
            } else {
                $WalletRecord = [];
                $Count = 0;
                $WalletRecord['in'] = $this->WalletRecordRepositoryV2->GetByUidAndTypeOnRange($Option, 'in', $UserId);
                $WalletRecord['out'] = $this->WalletRecordRepositoryV2->GetByUidAndTypeOnRange($Option, 'out', $UserId);
                $Count += $WalletRecord['in']->count();
                $Count += $WalletRecord['out']->count();
                return array('count' => $Count, 'data' => $WalletRecord);
            }
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function SaveEcpay($EcpayInfo)
    {
        try {
            $this->EcpayRepositoryV2->Create($EcpayInfo);
            return $EcpayInfo;
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function SaveEcpayBack($EcpayBackInfo)
    {
        try {
            $this->EcpayBackRepositoryV2->Create($EcpayBackInfo);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
}
