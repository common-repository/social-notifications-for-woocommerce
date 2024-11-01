<?php

class SU_WC_WA_Message_API {
    protected const API_URL = 'https://rcmapi.instaalerts.zone/services/rcm/sendMessage';
    protected static $instance = NULL;

    public static function get_instance( $integrator ) {
        NULL === self::$instance && self::$instance = new self( $integrator );
        return self::$instance;
    }

    private function __construct() {
    }

    public static function encode_message( $sender, $number, $message, $url='', $tid='', $mediaType='' ) {
        if ( empty( $sender ) || empty( $number ) || ( empty( $message ) && empty( $url ) && empty( $tid ) ) ) {
            throw new Exception( "Sender, Message/URL/Template ID, and number - all are required for encoding a WhatsApp message." );
        }

        if ( !empty( $url ) && empty( $mediaType ) ) {
            throw new Exception( "Media Type not specified for the media URL." );
        }

        if (!empty($url)) {
            $content = [
                'type' => 'ATTACHMENT',
                'attachment' => [
                    'url' => $url,
                    'type' => $mediaType,
                ]
            ];
            if (!empty($message)) {
                if ($mediaType == 'document') {
                    $content['attachment']['fileName'] = $message;
                } else {
                    $content['attachment']['caption'] = $message;
                }
            }
        } else if (!empty($tid)) {
            $content = [
                'type' => 'TEMPLATE',
                'template' => [
                    'templateId' => $tid
                ]
            ];
            if (!empty($message)) {
                $content['template']['parameterValues'] = (object) json_decode( $message );
            }
        } else {
            $content = [
                'type' => 'TEXT',
                'text' => $message,
                'preview_url' => false
            ];
        }
    
        $data = [
            'message' => [
                'channel' => 'WABA',
                'content' => $content,
                'recipient' => [
                    'to' => $number,
                    'recipient_type' => 'individual'
                ],
                'sender' => [
                    'from' => $sender
                ],
            ],
            'metaData' => [
                'version' => 'v1.0.9'
            ]
        ];

        return json_encode( $data );
    }

    public static function send_message( $token, $data ) {
        //Validate content
        if ( empty( $token ) || empty( $data ) ) {
            throw new Exception( "Token and Data - all are required for sending a WhatsApp message." );
        }

        $response = wp_remote_post( self::API_URL, [
            'headers' => [
                'Authentication' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
            'sslverify' => false,
            'body' => $data,
        ] );

        //Valdate response
        if ( is_wp_error( $response ) ) {
            throw new Exception( $response->get_error_message() );
        }
        if ( is_array( $response ) ) {
            $response = $response['body'];
        }

        //Return the response
        return $response;
    }
};
