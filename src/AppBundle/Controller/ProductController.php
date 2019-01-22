<?php
/**
 * Created by PhpStorm.
 * User: sunilshashidhar
 * Date: 1/20/19
 * Time: 10:07 PM
 */
namespace AppBundle\Controller;

use AppBundle\Entity\ProductBundle;
use AppBundle\Entity\ProductDiscount;
use AppBundle\Entity\Products;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Services\Constants;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductController extends Controller{

    public $isAdminUser = false;
    public $request;


    public function createProductsAction(Request $request){
        $this->request = $request;
        $response = $this->createProducts();
        return $response;
    }

    public function updateProductsAction(Request $request){
        $this->request = $request;
        $response = $this->updateProducts();
        return $response;
    }

    public function deleteProductsAction(Request $request){
        $this->request = $request;
        $response = $this->deleteProducts();
        return $response;
    }

    public function getProductsAction(Request $request){
        $this->request = $request;
        $response = $this->getProducts();
        return $response;
    }

    public function createProducts(){
        $returnData = self::initializeAndValidateRequest('create');
        if(!empty($returnData['status']) && $returnData['status'] === Constants::FAILURE){
            $response = new JsonResponse(
                $returnData,
                ($returnData['message'] == Constants::UNAUTHORIZED_ACCESS)?JsonResponse::HTTP_FORBIDDEN:JsonResponse::HTTP_BAD_REQUEST
            );
        }else{
            $returnData = array();
            $createPostData = json_decode($this->request->getContent(),true);
            $createPostData = $createPostData['Products'];
            foreach($createPostData as $data){

                $id = self::insertProduct($data);
                if(isset($data['bundle_desc'])){
                    self::insertProductBundle($id,$data['bundle_desc']);
                    self::updateBundleQuantity($id);
                }
                if(isset($data['discount_type'])){
                    self::insertProductDiscount($id,$data);
                }
                $returnData['ids'][] = $id;
            }
            $returnData['status'] = Constants::SUCCESS;
            $response = new JsonResponse(
                $returnData,
                JsonResponse::HTTP_CREATED
            );
        }
        return $response;
    }

    public function updateProducts(){
        $returnData = self::initializeAndValidateRequest('update');
        if(!empty($returnData['status']) && $returnData['status'] === Constants::FAILURE){
            $response = new JsonResponse(
                $returnData,
                ($returnData['message'] == Constants::UNAUTHORIZED_ACCESS)?JsonResponse::HTTP_FORBIDDEN:JsonResponse::HTTP_BAD_REQUEST
            );
        }else{
            $returnData = array();
            $createPostData = json_decode($this->request->getContent(),true);
            $createPostData = $createPostData['Products'];
            foreach($createPostData as $data){
                $id = self::updateProduct($data);
                if(isset($data['discount_type'])){
                    self::updateProductDiscount($data);
                }
                if(isset($data['bundle_desc'])){
                    self::updateProductBundle($id,$data['bundle_desc']);
                    self::updateBundleQuantity($id);
                }
            }
            $returnData['status'] = Constants::SUCCESS;
            $returnData['message'] = "Data Successfully Updated";
            $response = new JsonResponse(
                $returnData,
                JsonResponse::HTTP_OK
            );
        }
        return $response;
    }

    public function deleteProducts(){
        $returnData = self::initializeAndValidateRequest('delete');
        if(!empty($returnData['status']) && $returnData['status'] === Constants::FAILURE){
            $response = new JsonResponse(
                $returnData,
                ($returnData['message'] == Constants::UNAUTHORIZED_ACCESS)?JsonResponse::HTTP_FORBIDDEN:JsonResponse::HTTP_BAD_REQUEST
            );
        }else{
            $returnData = array();
            $createPostData = json_decode($this->request->getContent(),true);
            $createPostData = $createPostData['Products'];
            foreach($createPostData as $data){
                $product_ids[] = $data['id'];
            }
            $bundleProductIds = self::getBundleProductIds($product_ids);
            foreach($bundleProductIds as $bundleId){
                $product_ids[] = $bundleId['bundle_product_id'];
            }
            $product_ids = array_map('intval',$product_ids);
            self::deleteProductByIds($product_ids);
            $returnData['status'] = Constants::SUCCESS;
            $returnData['message'] = "Products Successfully Deleted";
            $returnData['deletedProjectIds'] = $product_ids;
            $response = new JsonResponse(
                $returnData,
                JsonResponse::HTTP_OK
            );
        }
        return $response;
    }

    public function getProducts(){
        $queryData = self::getQueryParams($this->request->query);
        $res = self::getProductDetails($queryData);
        foreach($res as $key=>$data){
            if(isset($data['type'])) {
                if ($data['type'] == 'FIXED') {
                    $data['price'] = $data['price'] - $data['value'];
                } else if ($data['type'] == 'PERCENTAGE') {
                    $data['price'] = $data['price'] - (($data['value'] * $data['price']) / 100);
                }
            }
                $data['price'] = $data['price'] . " " . $data['currency_type'];
                unset($data['currency_type']);
                unset($data['is_valid']);
                unset($data['reg_on']);
                unset($data['currency_type_mapping']);
                unset($data['type']);
                unset($data['value']);
                $data['quantity available'] = $data['quanity'];
                unset($data['quanity']);
                $resData[] = ($data);
        }
        $res =$resData;
        $response['status'] = Constants::SUCCESS;
        $response['data'] = $res;
        $response = new JsonResponse(
            $response,
            JsonResponse::HTTP_OK
        );
        return $response;
    }

    public function getProductDetails($queryData){
        $queryData['page'] = (isset($queryData['page']) && !empty($queryData['page']))?$queryData['page']:1;
        $queryData['limit'] = (isset($queryData['limit']) && !empty($queryData['limit']))?$queryData['limit']:1;
        $offset = ($queryData['page']-1)*$queryData['limit'];
        $append_query = '';
        if(isset($queryData['type'])){
            $append_query = "and a.".$queryData['type']." in (".implode(',',$queryData['values']).")";
        }
        $RAW_SQL_QUERY = "SELECT a.*,b.currency_type,c.type,c.value from products as a
                          join currency as b on a.currency_type_mapping=b.id
                          left join product_discount as c on a.id=c.product_id where a.is_valid=1 ".$append_query." limit ".$queryData['limit']." offset ".$offset.";";
        $em = $this->getDoctrine()->getManager();
        $statement = $em->getConnection()->prepare($RAW_SQL_QUERY);
        $statement->execute();
        $result = $statement->fetchAll();
        return $result;
    }

    public function getQueryParams($query){
        $queryData = array();
        if(!empty($query->get('type'))){
            $queryData['type'] = $query->get('type');
        }
        if(!empty($query->get('values'))){
            $queryData['values'] = $query->get('values');
        }
        if(!empty($query->get('page'))){
            $queryData['page'] = $query->get('page');
        }else{
            $queryData['page'] = 1;
        }
        if(!empty($query->get('limit'))){
            $queryData['limit'] = $query->get('limit');
        }else{
            $queryData['limit'] = 10;
        }
        return $queryData;
    }

    public function insertProductDiscount($productId,$data){
        $productDiscountEntity = new ProductDiscount();
        $productDiscountEntity->setProductId($productId);
        $productDiscountEntity->setIsValid(1);
        $productDiscountEntity->setType($data['discount_type']);
        $productDiscountEntity->setValue($data['discount_value']);
        $em = $this->getDoctrine()->getManager();
        $em->persist($productDiscountEntity);
        $em->flush();
        return $productDiscountEntity->getId();
    }

    public function updateProductDiscount($data){
        $em = $this->getDoctrine()->getManager();
        $productDiscountEntity = self::getProductDiscountById($data['id']);
        if (!$productDiscountEntity) {
            return self::insertProductDiscount($data['id'],$data);
        }
        $productDiscountEntity->setType($data['discount_type']);
        $productDiscountEntity->setValue($data['discount_value']);
        $em->flush();
        return $productDiscountEntity->getId();
    }

    public function updateBundleQuantity($bundleProductId){
        $RAW_QUERY = "SELECT a.product_id as product_id,a.quantity as bundle_quantity,b.quanity as available_quantity FROM product_bundle as a join products as b on a.product_id=b.id where a.bundle_product_id = '".$bundleProductId."';";
        $em = $this->getDoctrine()->getManager();
        $statement = $em->getConnection()->prepare($RAW_QUERY);
        $statement->execute();
        $result = $statement->fetchAll();
        foreach($result as $data){
            $maxQuantity[$data['product_id']] = $data['available_quantity']/$data['bundle_quantity'];
        }
        $productEntity = self::getProductById($bundleProductId);
        if (!$productEntity) {
            throw $this->createNotFoundException(
                'No product found for id '.$bundleProductId
            );
        }
        $productEntity->setQuanity(min($maxQuantity));
        $em->flush();
    }

    public function insertProduct($data){
        $productEntity = new Products();
        $productEntity->setName($data['name']);
        $productEntity->setDescription($data['description']);
        $productEntity->setPrice($data['price']);
        $productEntity->setIsValid(1);
        $productEntity->setCurrencyTypeMapping(self::getCurrencyId($data['currency_type']));
        $productEntity->setQuanity(isset($data['quantity_available'])?$data['quantity_available']:0);
        $em = $this->getDoctrine()->getManager();
        $em->persist($productEntity);
        $em->flush();
        return $productEntity->getId();
    }

    public function updateProduct($data){
        $em = $this->getDoctrine()->getManager();
        $productEntity = self::getProductById($data['id']);
        if(isset($data['quantity_available'])){
            $productEntity->setQuanity($data['quantity_available']);
        }
        if(isset($data['name'])){
            $productEntity->setName($data['name']);
        }
        if(isset($data['price'])){
            $productEntity->setPrice($data['price']);
        }
        if(isset($data['description'])){
            $productEntity->setDescription($data['description']);
        }
        if(isset($data['currency_type'])){
            $productEntity->setCurrencyTypeMapping(self::getCurrencyId($data['currency_type']));
        }
        $em->flush();
        return $productEntity->getId();
    }

    public function insertProductBundle($bundleProductId, $bundleDesc){
        foreach($bundleDesc as $bundleData){
            $bundleEntity = new ProductBundle();
            $bundleEntity->setBundleProductId($bundleProductId);
            $bundleEntity->setProductId(self::getProductIdByName($bundleData['product_name']));
            $bundleEntity->setQuantity($bundleData['quantity']);
            $em = $this->getDoctrine()->getManager();
            $em->persist($bundleEntity);
            $em->flush();
        }
    }

    public function updateProductBundle($bundleProductId, $bundleDesc){
        foreach($bundleDesc as $bundleData){
            $bundleEntity = self::getProductBundleById($bundleProductId,self::getProductIdByName($bundleData['product_name']));
            if(!$bundleEntity){
                self::insertProductBundle($bundleProductId,array($bundleData));
            }else{
                $bundleEntity->setProductId(self::getProductIdByName($bundleData['product_name']));
                $bundleEntity->setQuantity($bundleData['quantity']);
                $em = $this->getDoctrine()->getManager();
                $em->persist($bundleEntity);
                $em->flush();
            }
        }
    }

    public function getCurrencyId($currency_type){
        $RAW_QUERY = "SELECT * FROM currency where currency_type = '".$currency_type."';";
        $em = $this->getDoctrine()->getManager();
        $statement = $em->getConnection()->prepare($RAW_QUERY);
        $statement->execute();
        $result = $statement->fetchAll();
        return $result[0]['id'];
    }

    public function getBundleProductIds($product_ids){
        $RAW_QUERY = "SELECT distinct(bundle_product_id) FROM product_bundle where product_id in (".implode(',',$product_ids).");";
        $em = $this->getDoctrine()->getManager();
        $statement = $em->getConnection()->prepare($RAW_QUERY);
        $statement->execute();
        $result = $statement->fetchAll();
        return $result;
    }

    public function getTotalPrice($productId,$quantity){
        $res = self::getProductDetails(array('type'=>'id','values'=>array($productId)));
        $data = $res[0];
        if(isset($data['type'])) {
            if ($data['type'] == 'FIXED') {
                $data['price'] = $data['price'] - $data['value'];
            } else if ($data['type'] == 'PERCENTAGE') {
                $data['price'] = $data['price'] - (($data['value'] * $data['price']) / 100);
            }
        }
        $totalPrice = $data['price'] * $quantity;
        return $totalPrice;
    }

    public function initializeAndValidateRequest($method){
        $request = $this->request;
        $username = $request->headers->get('php-auth-user');
        $password = $request->headers->get('php-auth-pw');
        $returnData = array();
        if(isset($username) && !empty($username) && $username !== null &&
            isset($password) && !empty($password) && $password !== null){
            $em = $this->getDoctrine()->getManager();
            $RAW_QUERY = "SELECT * FROM users as a join admin_user_mapping as b on a.id=b.user_id where a.email = '".$username."' and a.password ='".$password."';";
            $statement = $em->getConnection()->prepare($RAW_QUERY);
            $statement->execute();
            $result = $statement->fetchAll();
            if(count($result)>0){
                $this->isAdminUser = true;
            }
        }
        if(!$this->isAdminUser) {
            $returnData['status'] = Constants::FAILURE;
            $returnData['message'] = Constants::UNAUTHORIZED_ACCESS;
            return $returnData;
        }

        $callableMethod = 'validate'.ucwords($method).'Request';
        return self::$callableMethod();

    }

    public function validateCreateRequest(){
        $request = $this->request;
        $createPostData = json_decode($request->getContent(),true);
        $createPostData = $createPostData['Products'];
        $returnData = array();
        foreach($createPostData as $data){
            if(!isset($data['name']) || !isset($data['description']) || !isset($data['price']) || !isset($data['currency_type'])){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Name,description,price or currency_type  fields cannot be empty";
            }else if($data['price']<=0){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Price should be greater than zero";
            }else if(!isset($data["bundle_desc"]) && (!isset($data['quantity_available']) || $data['quantity_available']<=0)){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "quantity_available field should not be empty and available quantity should be greater than zero";
            }else if(isset($data["bundle_desc"]) && isset($data['quantity_available'])){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Quantity Available cannot be set in case of Bundle products";
            }
            if(isset($returnData['status'])){
                break;
            }
        }
        return $returnData;
    }

    public function validateUpdateRequest(){
        $request = $this->request;
        $updatePostData = json_decode($request->getContent(),true);
        $updatePostData = $updatePostData['Products'];
        $returnData = array();
        foreach($updatePostData as $data){
            if(isset($data['id']) && $data['id']<=0){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Product Id cannot be empty";
            }else if(!self::getProductById($data['id'])){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Product Not Found. Please enter a valid product id";
            } else if(isset($data['price']) && $data['price']<=0){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Price should be greater than zero";
            }else if(isset($data['quantity_available']) && $data['quantity_available']<=0){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Available quantity should be greater than zero";
            }else if(isset($data["bundle_desc"]) && isset($data['quantity_available'])){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Quantity Available cannot be set in case of Bundle products";
            }
            if(isset($returnData['status'])){
                break;
            }
        }
        return $returnData;
    }

    public function validateDeleteRequest(){
        $request = $this->request;
        $updatePostData = json_decode($request->getContent(),true);
        $updatePostData = $updatePostData['Products'];
        $returnData = array();
        foreach($updatePostData as $data){
            if(isset($data['id']) && $data['id']<=0){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Product Id cannot be empty";
            }else if(!self::getProductById($data['id'])){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Product Not Found. Please enter a valid product id";
            }
            if(isset($returnData['status'])){
                break;
            }

        }
        return $returnData;
    }

    public function getProductBundleById($productBundleId, $productId){
        $em = $this->getDoctrine()->getManager();
        $product = $em->getRepository('AppBundle:ProductBundle')->findOneBy(array('bundleProductId'=>$productBundleId,'productId'=>$productId));
        return $product;
    }

    public function getProductDiscountById($productId){
        $em = $this->getDoctrine()->getManager();
        $product = $em->getRepository('AppBundle:ProductDiscount')->findOneByProductId($productId);
        return $product;
    }

    public  function getProductById($productId){
        $em = $this->getDoctrine()->getManager();
        $product = $em->getRepository('AppBundle:Products')->findOneBy(array('id'=>$productId,'isValid'=>1));
        return $product;
    }

    public function getProductIdByName($productName){
        $RAW_QUERY = "SELECT id FROM products where name = '".$productName."';";
        $em = $this->getDoctrine()->getManager();
        $statement = $em->getConnection()->prepare($RAW_QUERY);
        $statement->execute();
        $result = $statement->fetchAll();
        if(count($result)>0)
            return $result[0]['id'];
        else
            return 0;
    }

    public function deleteProductByIds($productIds){
        $em = $this->getDoctrine()->getManager();
        $product = $em->getRepository('AppBundle:Products');
        foreach ($product->findById($productIds) as $obj) {
            $obj->setIsValid(0);
        }
        $em->flush();
    }




}