<?php
/**
 * FecShop file.
 *
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */

namespace fecshop\app\appserver\modules\Customer\controllers;

use fecshop\app\appserver\modules\AppserverTokenController;
use Yii;
use \Firebase\JWT\JWT;

/**
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0
 */
class ProductfavoriteController extends AppserverTokenController
{
    public $enableCsrfValidation = false ;
    public $pageNum;
    public $numPerPage = 20;
    public $_page = 'p';

    public function initFavoriteParam()
    {
        $pageNum = Yii::$app->request->get($this->_page);
        $this->pageNum = $pageNum ? $pageNum : 1;
    }

    public function actionIndex()
    {
        $this->initFavoriteParam();
        $identity = Yii::$app->user->identity;
        $filter = [
            'pageNum'    => $this->pageNum,
            'numPerPage'=> $this->numPerPage,
            'orderBy'    => ['updated_at' => SORT_DESC],
            'where'            => [
                ['user_id' => $identity['id']],
            ],
            'asArray' => true,
        ];
        $data  = Yii::$service->product->favorite->list($filter);
        
        $coll  = $data['coll'];
        $count = $data['count'];
        //$pageToolBar = $this->getProductPage($count);
        $product_arr = $this->getProductInfo($coll);

        return [
            'code'          => 200,
            'productList'   => $product_arr,
            'count'         => $count,
            'numPerPage'    => $numPerPage,
        ];
    }

    // 得到产品的一些信息，来显示Favorite 的产品列表。
    public function getProductInfo($coll)
    {
        $product_ids = [];
        $favorites = [];
        foreach ($coll as $one) {
            $p_id = (string)$one['product_id'];
            $product_ids[] = $one['product_id'];
            $favorites[$p_id] = [
                'updated_at' => $one['updated_at'],
                'favorite_id' => (string) $one['_id'],
            ];
        }
        // 得到产品的信息
        $product_filter = [
            'where'            => [
                ['in', '_id', $product_ids],
            ],
            'select' => [
                'name', 'image',
                'price', 'special_price',
                'special_from', 'special_to',
                'url_key',
            ],
            'asArray' => true,
        ];
        $data = Yii::$service->product->coll($product_filter);
        $product_arr = [];
        if (is_array($data['coll']) && !empty($data['coll'])) {
            foreach ($data['coll'] as $one) {
                $p_id = (string) $one['_id'];
                $one['updated_at'] = $favorites[$p_id]['updated_at'];
                $one['favorite_id'] = $favorites[$p_id]['favorite_id'];
                $main_img = isset($one['image']['main']['image']) ? $one['image']['main']['image'] : '';
                $one['imgUrl'] = Yii::$service->product->image->getResize($main_img,296,false);
                $priceInfo = Yii::$service->product->price->getCurrentCurrencyProductPriceInfo($one['price'], $one['special_price'],$one['special_from'],$one['special_to']);
                $one['price_info'] = $priceInfo;
                $one['product_id'] = $p_id;
                $one['name'] = Yii::$service->store->getStoreAttrVal($one['name'],'name');
                $product_arr[] = $one;
            }
        }

        return \fec\helpers\CFunc::array_sort($product_arr, 'updated_at', 'desc');
    }

    /**
     * @property $favorite_id|string
     */
    public function actionRemove()
    {
        $favorite_id = Yii::$app->request->post('favorite_id');
        Yii::$service->product->favorite->currentUserRemove($favorite_id);
        return [
            'code' => 200,
            'content' => 'remove favorite success',
        ];
    }

    

    
}