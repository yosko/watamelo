<?php

namespace Watamelo\Data;

use Yosko\Loggable;

class User extends Data implements Loggable
{
    public ?int $id = null;
    public int $level = 1;
    public string $login = '';
    public string $password = '';
    public string $creation = '';
    public string $bio = '';

    private array $errors = [];
    private bool $isLoggedIn = false;
    private bool $secure = false;

    public function getTitle(): string
    {
        return $this->login;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $code, bool $value): void
    {
        $this->errors[$code] = $value;
    }

    public function isLoggedIn(): bool
    {
        return $this->isLoggedIn;
    }

    public function setIsLoggedIn(bool $isLoggedIn): void
    {
        $this->isLoggedIn = $isLoggedIn;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function setSecure(bool $secure): void
    {
        $this->secure = $secure;
    }
}