<?php

function pre()
{
    echo (php_sapi_name() !== 'cli') ? '<pre>' : '';
    foreach (func_get_args() as $arg) {
        echo preg_replace('#\n{2,}#', "\n", print_r($arg, true));
    }
    echo (php_sapi_name() !== 'cli') ? '</pre>' : '';
    exit();
}

function get_direct_value($table, $columnRequired, $columnNameToCompare, $columnValueToCompare)
{
    $db      = \Config\Database::connect();
    $builder = $db->table($table);
    $value   = $builder->select($columnRequired)->getWhere(array($columnNameToCompare=>$columnValueToCompare))->getResultArray();
    if ($value) {
        return $value[0][$columnRequired];
    } else {
        return 0;
    }
}


function get_direct_value_custom_where($table, $columnRequired, $where = array())
{
    $db      = \Config\Database::connect();
    $builder = $db->table($table);
    $value   = $builder->select($columnRequired)->getWhere($where)->getResultArray();
    if ($value) {
        return $value[0][$columnRequired];
    } else {
        return 0;
    }
}


function app_name()
{
    return get_direct_value('general_settings', 'value', 'name', 'app_name');
}

function get_office_ip()
{
    $ip = get_direct_value('general_settings', 'value', 'name', 'office_ip');
    return explode(',', $ip);
}

function RandomString($length = 10)
{
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)))), 1, $length);
}

function countRow($table, $where)
{
    $db      = \Config\Database::connect();
    $builder = $db->table($table);
    $builder->selectCount('id');
    $builder->where($where);
    $value = $builder->get()->getResultArray();

    if ($value) {
        return $value[0]['id'];
    } else {
        return 0;
    }
}

function SumColumn($table, $where, $column)
{
    $db      = \Config\Database::connect();
    $builder = $db->table($table);
    $builder->selectSum($column);
    $builder->where($where);
    $value = $builder->get()->getResultArray();
    if ($value[0][$column] !== '') {
        return $value[0][$column];
    } else {
        return 0;
    }
}

function BgColor()
{
    $color = ['bg-red','bg-pink','bg-purple','bg-deep-purple','bg-indigo','bg-blue','bg-light-blue','bg-blue','bg-teal','bg-green','bg-light-green','bg-lime','bg-yellow','bg-amber','bg-orange','bg-deep-orange','bg-brown','bg-grey','bg-blue-grey'];
    shuffle($color);
    return $color[0];
}

function generateKey($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function get_g_setting_val($column)
{
    $db      = \Config\Database::connect();
    $builder = $db->table('general_settings');
    return $builder->getWhere(array('name'=>$column))->getRowArray()['value'];
}

function UploadFile($FILE)
{
    $url        = get_g_setting_val('dcloud_api');
    $X_Key      = get_g_setting_val('x-key');
    $X_Secret   = get_g_setting_val('x-secret');
    $ch = curl_init();
    $RealTitle = $FILE['name'];

    $postfields['file'] = new CurlFile($FILE['tmp_name'], $FILE['type'], $RealTitle);
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postfields,
        CURLOPT_HTTPHEADER => array(
            'X-Key: ' . $X_Key,
            'X-Secret: ' . $X_Secret
        ),
    ));

    $response = curl_exec($ch);
    if (!curl_errno($ch)) {
        curl_close($ch);
        return json_decode($response, true);
    } else {
        curl_close($ch);
        $errmsg = curl_error($ch);
        return $errmsg;
    }
}

function DeleteDcloudFile($FILES)
{
    $url        = get_g_setting_val('dcloud_api');
    $X_Key      = get_g_setting_val('x-key');
    $X_Secret   = get_g_setting_val('x-secret');
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_POSTFIELDS => json_encode($FILES),
        CURLOPT_HTTPHEADER => array(
            'X-Key: ' . $X_Key,
            'X-Secret: ' . $X_Secret
        ),
    ));

    $response = curl_exec($ch);
    if (!curl_errno($ch)) {
        curl_close($ch);
        return json_decode($response, true);
    } else {
        curl_close($ch);
        // $errmsg = curl_error($ch);
        // return $errmsg;
        return array('status'=>false,'message'=>'Failed To upload');
    }
}

//**************************************************************************************SMS Gateway************************************************************************************************
    function send_gateway_message($contact, $msg, $template_id=null)
    {
        if (get_g_setting_val('message_gateway') == 'gupshup') {
            return send_smsgupshup($contact, $msg);
        } else {
            return send_bulksmsgateway($contact, $template_id, $msg);
        }
    }

    function send_smsgupshup($phone, $msg)
    {
        $curl = curl_init();
        $new = str_replace('&', '%26', $msg);
        $new = str_replace(' ', '%20', $new);
        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://enterprise.smsgupshup.com/GatewayAPI/rest?method=sendMessage&msg=" . $new . "&send_to=" . $phone . "&msg_type=Text&userid=2000190745&auth_scheme=Plain&password=jdHq2QoSg&v=1.1&format=TEXT",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: text/plain"
        ) ,
    ));
        $response = curl_exec($curl);
        // print_arrays($response);exit;
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            // echo "cURL Error #:" . $err;
            return 0;
        } else {
            $str1 = explode('|', $response);
            $str = str_replace(' ', '', $str1[0]);
            if ($str == 'success') {
                return 1;
            } else {
                return 0;
            }
        }
    }

    function send_bulksmsgateway($number, $template_id, $message)
    {
        $username="Zaeem23";
        $password ="7208992803";
        $sender="DARPRL";
        $url="http://api.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($number)."&sender=".urlencode($sender)."&message=".urlencode($message)."&type=".urlencode('3')."&template_id=".urlencode($template_id);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: text/plain"
        ) ,
    ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            // echo "cURL Error #:" . $err;
            return 0;
        } else {
            // echo $response;exit;
            $res = json_decode($response, true);
            if ($res['status'] == 'success') {
                return 1;
            } else {
                return 0;
            }
        }
    }
//**************************************************************************************Close SMS Gateway************************************************************************************************
