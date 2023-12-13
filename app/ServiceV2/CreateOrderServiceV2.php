<?php
namespace App\ServiceV2;

use App\Factorise;
use App\RepositoryV2\RestaurantRepositoryV2;
use Illuminate\Support\Str;

class CreateOrderServiceV2
{
    private $RestaurantRepositoryV2;
    private $Factorise;
    private $Restaurant;
    public function __construct(Factorise $Factorise, RestaurantRepositoryV2 $RestaurantRepositoryV2)
    {
        $this->Factorise = $Factorise;
        $this->RestaurantRepositoryV2 = $RestaurantRepositoryV2;
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
        return $this->Restaurant->Change($OrderInfo, $Order);
    }

}