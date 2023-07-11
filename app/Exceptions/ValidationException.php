<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Foundation\Testing\HttpException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Auth\AuthenticationException;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;

/**
 * 業務エラー系の例外クラス
 */
class ValidationException extends Exception{
    
    private $messageBag;
    private $errorMessage;

    public function __construct($message){
        if (gettype($message) == 'string') {
            $this->errorMessage = $message;
        }else{
            $this->messageBag = $message;
        }
    }


    /**
     * MessageBagを返す
     */
    public function getMessageBag() : MessageBag{
        return $this->messageBag;
    }

    /**
     * エラーメッセージを配列で返す
     */
    public function getMessages() : array{
        return $this->messageBag->messages();
    }

    /**
     * 先頭のエラーメッセージを返す
     */
    public function getFirstMessage() : string{
        return $this->messageBag->first();
    }

    /**
     * 引数で渡されたエラーメッセージを返す(messageBagがあれば一番最初のメッセージ)
     * @return string
     */
    public function getErrorMessage() : string{
        if ($this->messageBag) {
            $this->errorMessage = $this->messageBag->first();
        }
        return $this->errorMessage;
    }

    /**
     * 引数で渡されたエラーメッセージを返す(messageBagがあれば配列で返す)
     * @return string
     */
    public function getErrorMessages() : array{
        $result = null;
        if ($this->messageBag) {
            $result = $this->messageBag->messages();
        }else{
            $result = [$this->errorMessage];
        }
        return $result;
    }
}
