<?php
/**
 * Created by PhpStorm.
 * User: sunilshashidhar
 * Date: 1/23/19
 * Time: 1:58 AM
 */
namespace AppBundle\Controller;

use AppBundle\Entity\Orderdetails;
use AppBundle\Entity\Orders;
use AppBundle\Entity\ProductBundle;
use AppBundle\Entity\ProductDiscount;
use AppBundle\Entity\Products;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Services\Constants;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\ProductController;

class OrderController extends Controller{
    private $isLoggedInUser = false;
    private $userDetails;
    private $request;
    private $requestData;

    public function createOrderAction(Request $request){
        $this->request = $request;
        $response = $this->createOrder();
        return $response;
    }

    public function viewOrdersAction(Request $request){
        $this->request = $request;
        $response = $this->viewOrders();
        return $response;
    }

    public function createOrder(){
        $returnData = self::validateRequest('create');
        if(!empty($returnData['status']) && $returnData['status'] === Constants::FAILURE){
            $response = new JsonResponse(
                $returnData,
                ($returnData['message'] == Constants::UNAUTHORIZED_ACCESS)?JsonResponse::HTTP_FORBIDDEN:JsonResponse::HTTP_BAD_REQUEST
            );
        }else{
            $postData = $this->requestData;
            $id = self::insertOrder();
            $orderTotalAmount = 0;
            foreach($postData as $data){
                self::insertOrderDetails($id,$data);
                $orderTotalAmount = $orderTotalAmount + ProductController::getTotalPrice($data['id'],$data['quantity']);
            }
            self::updateOrder($id,array('total_amount'=>$orderTotalAmount));
            //need to update the product availability for bundle as well as normal products
            $returnData['status'] = Constants::SUCCESS;
            $returnData['message'] = "Order Successfully Created";
            $response = new JsonResponse(
                $returnData,
                JsonResponse::HTTP_OK
            );
        }
        return $response;
    }

    public function viewOrders(){
        $returnData = self::validateRequest('view');
        if(!empty($returnData['status']) && $returnData['status'] === Constants::FAILURE){
            $response = new JsonResponse(
                $returnData,
                ($returnData['message'] == Constants::UNAUTHORIZED_ACCESS)?JsonResponse::HTTP_FORBIDDEN:JsonResponse::HTTP_BAD_REQUEST
            );
        }else{
            $queryData = ProductController::getQueryParams($this->request->query);
            $result = self::getOrderDetails($queryData);
            foreach($result as $resRow){
                $resp[$resRow['order_id']]['total_amount'] =  $resRow['total_amount'];
                $resp[$resRow['order_id']][] =  array('product_id'=>$resRow['product_id'],'quantity'=>$resRow['quantity'],'price'=>$resRow['price']);
            }
            $response['status'] = Constants::SUCCESS;
            $response['data'] = $resp;
            $response = new JsonResponse(
                $response,
                JsonResponse::HTTP_OK
            );

        }
        return $response;
    }

    public function getOrderDetails($queryData){
        $queryData['page'] = (isset($queryData['page']) && !empty($queryData['page']))?$queryData['page']:1;
        $queryData['limit'] = (isset($queryData['limit']) && !empty($queryData['limit']))?$queryData['limit']:1;
        $offset = ($queryData['page']-1)*$queryData['limit'];
        $append_query = '';
        if(isset($queryData['type'])){
            $append_query = " and a.".$queryData['type']." in (".implode(',',$queryData['values']).")";
        }
        $RAW_SQL_QUERY = "select b.order_id,a.total_amount,b.product_id,b.quantity,b.price,c.name as product_name from orders as a
                          join order_details as b on a.id=b.order_id join products as c on b.product_id=c.id
                          where a.user_id=".$this->userDetails['id'].$append_query." limit ".$queryData['limit']." offset ".$offset.";";
        $em = $this->getDoctrine()->getManager();
        $statement = $em->getConnection()->prepare($RAW_SQL_QUERY);
        $statement->execute();
        $result = $statement->fetchAll();
        return $result;
    }

    public function insertOrder(){
        $orderEntity = new Orders();
        $orderEntity->setUserId($this->userDetails['id']);
        $orderEntity->setTotalAmount(0);
        $em = $this->getDoctrine()->getManager();
        $em->persist($orderEntity);
        $em->flush();
        return $orderEntity->getId();
    }

    public function updateOrder($id,$what){
        $em = $this->getDoctrine()->getManager();
        $orderEntity = $em->getRepository('AppBundle:Orders')->find($id);
        if(isset($what['total_amount'])){
            $orderEntity->setTotalAmount($what['total_amount']);
        }
        $em->persist($orderEntity);
        $em->flush();
        return $orderEntity->getId();

    }

    public function insertOrderDetails($orderId,$data){
        $orderDetailsEntity = new Orderdetails();
        $orderDetailsEntity->setProductId($data['id']);
        $orderDetailsEntity->setQuantity($data['quantity']);
        $orderDetailsEntity->setOrderId($orderId);
        $orderDetailsEntity->setPrice(ProductController::getTotalPrice($data['id'],$data['quantity']));
        $em = $this->getDoctrine()->getManager();
        $em->persist($orderDetailsEntity);
        $em->flush();
        return $orderDetailsEntity->getId();
    }

    public function validateRequest($method){
        $request = $this->request;
        $username = $request->headers->get('php-auth-user');
        $password = $request->headers->get('php-auth-pw');
        $returnData = array();
        if(isset($username) && !empty($username) && $username !== null &&
            isset($password) && !empty($password) && $password !== null){
            $em = $this->getDoctrine()->getManager();
            $RAW_QUERY = "SELECT * FROM users as a where a.email = '".$username."' and a.password ='".$password."';";
            $statement = $em->getConnection()->prepare($RAW_QUERY);
            $statement->execute();
            $result = $statement->fetchAll();
            if(count($result)>0){
                $this->isLoggedInUser = true;
                $this->userDetails = $result[0];
            }
        }
        if(!$this->isLoggedInUser) {
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
        $this->requestData = $createPostData;
        $returnData = array();
        foreach($createPostData as $data){
            if(empty($data['id'])||empty($data['quantity'])){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Id or Quantity cannot be empty";
            }else if(!($productEntity = ProductController::getProductById($data['id']))){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Product Not Found. Please enter a valid product id";
            }else if($productEntity->getQuanity()<$data['quantity']){
                $returnData['status'] = Constants::FAILURE;
                $returnData['message'] = "Quantity not available";
            }
            if(isset($returnData['status'])){
                break;
            }
        }
        return $returnData;
    }

    public function validateViewRequest(){
        return array();
    }
}
?>