<?php

namespace Digiworld\DigiChat\Contracts;

interface DigiChatContract
{
    public function sendMessage($to, $message, $options = []);
}
