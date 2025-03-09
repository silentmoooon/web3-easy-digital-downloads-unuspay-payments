<?php

if (!defined('ABSPATH')) exit;

if (isset($_GET["platform"]) && ($_GET["platform"] == "UNUSPAY") && isset($_GET["type"]) && ($_GET["type"] == "UNUSPAYEDD")) {
    status_header(500);
    header('Content-Type: application/json; charset=utf-8');
    
    if (isset($_GET["status"]) && isset($_GET["order_id"])) {
        $data = [];
        $status = sanitize_text_field($_GET["status"]);
        $order_id = sanitize_text_field($_GET["order_id"]);

        if ($status == "completed") {
            edd_update_payment_status($order_id, 'publish');

            $data['code'] = 200;
            $data['result'] = 1;
            $data['message'] = '[Unuspay EDD] Success: Order payment status already updated.';
            
            status_header(200);
            echo wp_json_encode($data);
            exit();
        } else {
            $data['code'] = 500;
            $data['result'] = 0;
            $data['message'] = '[Unuspay EDD] Failed: Order status is incorrect.';

            echo wp_json_encode($data);
            exit();
        }
    } else {
        $data['code'] = 500;
        $data['result'] = 0;
        $data['message'] = '[Unuspay EDD] Failed: status, order_id and type didn\'t exist';

        echo wp_json_encode($data);
        exit();
    }

    echo wp_json_encode($data);
    exit();
}
