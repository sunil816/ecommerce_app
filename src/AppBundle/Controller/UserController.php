<?php
/**
 * Created by PhpStorm.
 * User: sunilshashidhar
 * Date: 1/21/19
 * Time: 12:42 AM
 */
namespace AppBundle\Controller;

use AppBundle\Entity\AdminUserMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Services\UserService;
use AppBundle\Entity\Users;

class UserController extends Controller{

    public $usersData;
    const FAILURE = "FAILURE";
    const SUCCESS = "SUCCESS";

    public function __construct(){
        $this->userService = new UserService();
    }

    public function createUsersAction(Request $request){
        $data = json_decode($request->getContent(),true);
        $response = $this->createUsers($data);
        return $response;
    }

    public function createUsers($usersData){
        $this->usersData = $usersData["Users"];
        $return_data = self::validateUsers();
        if(!empty($return_data) && ($return_data['status'] == self::FAILURE)){
            $response = new JsonResponse(
                $return_data,
                JsonResponse::HTTP_BAD_REQUEST
            );
        }else{
            try {
                $return_data = array();
                foreach ($this->usersData as $userData) {
                    $userEntity = new Users();
                    $userEntity->setFirstname($userData['name']);
                    $userEntity->setEmail($userData['email']);
                    $userEntity->setPassword($userData['password']);
                    $userEntity->setStatus("A");
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($userEntity);
                    $em->flush();
                    $return_data['ids'][] = $userEntity->getId();
                    if(isset($userData['admin']) && $userData['admin'] == 1){
                        $adminEntity = new AdminUserMapping();
                        $adminEntity->setUserId($userEntity->getId());
                        $adminEntity->setIsValid(1);
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($adminEntity);
                        $em->flush();
                    }
                }
                $return_data['status'] = "SUCCESS";
                $return_data['message'] = "Id successfully created";
                $response = new JsonResponse(
                    $return_data,
                    JsonResponse::HTTP_CREATED
                );
            }catch (Exception $e){
                $return_data['status'] = self::FAILURE;
                $return_data['message'] = $e->getMessage();
                $response = new JsonResponse(
                    $return_data,
                    JsonResponse::HTTP_CREATED
                );
            }

        }
        return $response;
    }

    public function validateUsers(){
        foreach($this->usersData as $userData){
            if(empty($userData['name']) || empty($userData['email']) || empty($userData['password'])){
                $return_data['status'] = self::FAILURE;
                $return_data['message'] = "Name, email or password cannot be empty";
                return $return_data;
            }
        }
        $return_data['status'] = self::SUCCESS;
        $return_data['message'] = "Data is Valid";
        return $return_data;
    }

}