<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\RESTful\ResourceController;
use Exception;
use \Firebase\JWT\JWT;

class Api extends ResourceController
{
    public function __construct()
    {
        helper(['function', 'date']);
        $this->db 		    = \Config\Database::connect();
        $this->validation   =  \Config\Services::validation();
    }
    
    public function sendOtp()
    {
        $rules = [
            "mobile " => "required|regex_match[/^[0-9]{10}$/]"
        ];
        $messages = [
            "mobile" => [
                "required" => "Enter mobile number"
            ]
        ];
        if (!$this->validate($rules, $messages)) {
            $response = [
                'status' => 500,
                'error' => true,
                'message' => $this->validator->getErrors(),
                'data' => []
            ];
            return $this->respondCreated($response);
        }
        
        try {
            $otp  = rand(1000, 9999);
            $mobile = $this->request->getPost('mobile');

            $builder = $this->db->table('otp_section');
            $builder->delete(['contact' => $mobile]);

            $data['otp']        = $otp;
            $data['contact']    = $mobile;
            $data['created_at'] = date('Y-m-d H:i:s');
            if ($builder->insert($data)) {
                $msg = "Dear User, ".$otp." is your code and is valid only for 5 min. Do not share the OTP with anyone Darwin";
                if (send_gateway_message($mobile, $msg, '1607100000000061803') == 1) {
                    $response = [
                        'status' => 200,
                        'error' => false,
                        'message' => 'OTP Send Successfully',
                        'data' => []
                    ];
                } else {
                    $response = [
                        'status' => 500,
                        'error' => true,
                        'message' => 'Failed to send OTP',
                        'data' => []
                    ];
                }
            }
            return $this->respondCreated($response);
        } catch (Exception $ex) {
            $response = [
                'status' => 401,
                'error' => true,
                'message' => $ex->getMessage(),
                'data' => []
            ];
            return $this->respondCreated($response);
        }
    }

    public function verifyOtp()
    {
        $rules = [
            "mobile " => "required|regex_match[/^[0-9]{10}$/]",
            "otp" => "required|regex_match[/^[0-9]{4}$/]",
        ];
        $messages = [
            "mobile" => [
                "required" => "Please enter mobile number"
            ],
            "otp" => [
                "required" => "Please enter OTP",
                "regex_match" => "Incorrect OTP format"
            ]
        ];
        if (!$this->validate($rules, $messages)) {
            $response = [
                'status' => 500,
                'error' => true,
                'message' => $this->validator->getErrors(),
                'data' => []
            ];
            return $this->respondCreated($response);
        }
        
        try {
            $mobile     = $this->request->getPost('mobile');
            $otp        = $this->request->getPost('otp');
            $builder = $this->db->table('otp_section');
            $otp_resp   = $builder->where(array('otp' => $otp,'contact' => $mobile))->get()->getRowArray();
            if ($otp_resp) {
                if ($otp_resp['otp'] == $otp) {
                    $builder = $this->db->table('otp_section');
                    $builder->delete(['contact' => $mobile]);


                    // Register User if not registered
                    $builder    = $this->db->table('registration');
                    $register   = $builder->where(array('mobile' => $mobile))->get()->getRowArray();
                    if (!$register) {
                        $data['mobile']     = $mobile;
                        $data['created_at'] = date('Y-m-d H:i:s');
                        $builder->insert($data);
                    }
                    $response = [
                        'status' => 200,
                        'error' => false,
                        'message' => 'Mobile Verified Successfully',
                        'data' => []
                    ];
                } else {
                    $response = [
                        'status' => 401,
                        'error' => true,
                        'message' => 'Invalid OTP',
                        'data' => []
                    ];
                }
            } else {
                $response = [
                    'status' => 401,
                    'error' => true,
                    'message' => 'Invalid OTP',
                    'data' => []
                ];
            }
            return $this->respondCreated($response);
        } catch (Exception $ex) {
            $response = [
                'status' => 401,
                'error' => true,
                'message' => $ex->getMessage(),
                'data' => []
            ];
            return $this->respondCreated($response);
        }
    }

    public function getModelList()
    {
        try {
            $builder = $this->db->table('model');
            $models   = $builder->get()->getResultArray();
            $data=  $models;
            return $this->respondCreated($response);
        } catch (Exception $ex) {
            $response = [
                'status' => 401,
                'error' => true,
                'message' => $ex->getMessage(),
                'data' => []
            ];
            return $this->respondCreated($response);
        }
    }
}
