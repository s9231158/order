<?php
namespace App\ServiceV2;

use App\Factorise;
use App\RepositoryV2\OrderInfo as OrderInfoRepositoryV2;
use App\RepositoryV2\Order as OrderRepositoryV2;
use App\RepositoryV2\Restaurant as RestaurantRepositoryV2;
use App\RepositoryV2\UserWallet as UserWalletRepositoryV2;
use App\RepositoryV2\WalletRecord as WalletRecordRepositoryV2;
use Illuminate\Support\Str;
use Throwable;
use App\ServiceV2\Ecpay;

class CreateOrder
{
    private $Ecpay;
    private $RestaurantRepositoryV2;
    private $Factorise;
    private $Restaurant;//不會在這使用會在Controller
    private $OrderRepositoryV2;
    private $OrderInfoRepositoryV2;
    private $UserWalletRepositoryV2;
    private $WalletRecordRepositoryV2;
    private $EcpayRepositoryV2;
    public function __construct(
        Ecpay $Ecpay,
        WalletRecordRepositoryV2 $WalletRecordRepositoryV2,
        UserWalletRepositoryV2 $UserWalletRepositoryV2,
        OrderInfoRepositoryV2 $OrderInfoRepositoryV2,
        OrderRepositoryV2 $OrderRepositoryV2,
        Factorise $Factorise,
        RestaurantRepositoryV2 $RestaurantRepositoryV2
    ) {
        $this->Ecpay = $Ecpay;
        $this->Factorise = $Factorise;
        $this->RestaurantRepositoryV2 = $RestaurantRepositoryV2;
        $this->OrderRepositoryV2 = $OrderRepositoryV2;
        $this->OrderInfoRepositoryV2 = $OrderInfoRepositoryV2;
        $this->UserWalletRepositoryV2 = $UserWalletRepositoryV2;
        $this->WalletRecordRepositoryV2 = $WalletRecordRepositoryV2;
    }
    public function CheckRestaurantInDatabase(int $Rid): bool
    {
        try {
            return $this->RestaurantRepositoryV2->CheckRestaurantInDatabase($Rid);
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
            return $this->OrderRepositoryV2->Create($Order)['id'];
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500 . $e->getMessage());
        }
    }
    public function SaveOrderInfo($OrderInfo)
    {
        try {
            return $this->OrderInfoRepositoryV2->Create($OrderInfo);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function DeductMoney($UserId, $Balance)
    {
        try {
            $this->UserWalletRepositoryV2->UpdateOnId($UserId, $Balance);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function GetWalletMoney($UserId, $Money)
    {
        try {
            return $this->UserWalletRepositoryV2->GetOnId($UserId);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function SaveWalletRecord($WalletRecord)
    {
        $this->Ecpay->SaveWalletRecord($WalletRecord);
    }
    public function SendEcpayApi($EcpayInfo)
    {
        return $this->Ecpay->SendEcpayApi($EcpayInfo);
    }
    public function UpdateWalletRecordFail($Uuid)
    {
        try {
            return $this->Ecpay->UpdateWalletRecordFail($Uuid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function UpdateOrederFail($Oid)
    {
        try {
            $this->OrderRepositoryV2->UpdateFailByOid($Oid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function UpdateWalletRecordSuccess($Uuid)
    {
        try {
            return $this->Ecpay->UpdateWalletRecordSuccess($Uuid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function UpdateOrederSuccess($Oid)
    {
        try {
            $this->OrderRepositoryV2->UpdateSuccessByOid($Oid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function GetOidByUuid($Uuid)
    {
        return $this->WalletRecordRepositoryV2->GetById($Uuid);
    }
    public function GetOrder($UserId, $Oid, $Option)
    {
        try {
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
    public function GetOrderInfo($UserId, $Oid)
    {
        try {
            $OrderInfo = $this->OrderRepositoryV2->GetByUidAndOid($UserId, $Oid);
            return $OrderInfo = $OrderInfo->map->only(['name', 'quanlity', 'price', 'description']);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function SaveEcpay($EcpayInfo)
    {
        try {
            $this->Ecpay->SaveEcpay($EcpayInfo);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function SaveEcpayBack($EcpayBackInfo)
    {
        try {
            $this->Ecpay->SaveEcpayBack($EcpayBackInfo);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
}
