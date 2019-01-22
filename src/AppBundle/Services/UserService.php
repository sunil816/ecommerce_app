<?php
/**
 * Created by PhpStorm.
 * User: sunilshashidhar
 * Date: 1/21/19
 * Time: 2:11 AM
 */
namespace AppBundle\Services;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserService extends Controller{

    public $usersData;
    const FAILURE = "FAILURE";
    const SUCCESS = "SUCCESS";
    public function __construct(){
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
                $userEntity = new Users();
                foreach ($this->usersData as $userData) {
                    $userEntity->setFirstname($userData['name']);
                    $userEntity->setEmail($userData['email']);
                    $userEntity->setPassword($userData['password']);
                    $userEntity->setStatus("A");
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($userEntity);
                    $em->flush();
                    $return_data['ids'][] =$userEntity->getId();


                }
            $return_data['status'] = "SUCCESS";
            $response = new JsonResponse(
                $return_data,
                JsonResponse::HTTP_CREATED
            );

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