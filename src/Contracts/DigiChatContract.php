<?php

namespace Digiworld\DigiChat\Contracts;

interface DigiChatContract
{
    public function sendMessage($to, $message, $options = []): array;
    public function getQr(): array;
    public function getStatus(): array;
    public function logout($withDeletion = false): array;
    public function ping(): array;
}
