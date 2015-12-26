<?php

namespace Discord;

use Discord\Exceptions\InviteInvalidException;
use Discord\Exceptions\LoginFailedException;
use Discord\Helpers\Guzzle;
use Discord\Parts\Guild\Invite;
use Discord\Parts\User\Client;

class Discord
{
    const VERSION = 'v2-alpha';

    protected $client;

    /**
     * Logs into the Discord servers.
     *
     * @param string $email
     * @param string $password
     * @param string $token
     * @return void
     */
    public function __construct($email = null, $password = null, $token = null)
    {
        $this->setToken($email, $password, $token);

        $request = Guzzle::get('users/@me');

        $this->client = new Client([
            'id'            => $request->id,
            'username'      => $request->username,
            'email'         => $request->email,
            'verified'      => $request->verified,
            'avatar'        => $request->avatar,
            'discriminator' => $request->discriminator
        ], true);
    }

    /**
     * Check the filesystem for the token.
     *
     * @param string $email
     * @return string|null
     */
    public function checkForCaching($email)
    {
        if (file_exists($_SERVER['PWD'] . '/discord/' . md5($email))) {
            $file = file_get_contents($_SERVER['PWD'] . '/discord/' . md5($email));

            return $file;
        }

        return null;
    }

    /**
     * Sets the token for the API.
     *
     * @param string $email
     * @param string $password
     * @param string $token
     * @return void
     */
    public function setToken($email, $password, $token)
    {
        if (!is_null($token)) {
            @define('DISCORD_TOKEN', $token);
            return;
        }

        if (!is_null($token = $this->checkForCaching($email))) {
            @define('DISCORD_TOKEN', $token);
            return;
        }

        $request = Guzzle::post('auth/login', [
            'email'     => $email,
            'password'  => $password
        ], true);

        try {
            if (!file_exists($_SERVER['PWD'] . '/discord')) {
                mkdir($_SERVER['PWD'] . '/discord');
            }
            
            file_put_contents($_SERVER['PWD'] . '/discord/' . md5($email), $request->token);
        } catch (\Exception $e) {
        }

        @define('DISCORD_TOKEN', $request->token);
        return;
    }

    /**
     * Logs out of Discord.
     *
     * @return boolean
     */
    public function logout()
    {
        $request = Guzzle::post('auth/logout', [
            'token' => DISCORD_TOKEN
        ]);

        $this->client = null;

        return true;
    }

    /**
     * Accepts a Discord channel invite.
     *
     * @param string $code
     * @return Invite
     */
    public function acceptInvite($code)
    {
        try {
            $request = Guzzle::post("invite/{$code}");
        } catch (\Exception $e) {
            throw new InviteInvalidException('The invite is invalid or has expired.');
        }

        return new Invite([
            'code'      => $request->code,
            'guild'     => $request->guild,
            'xkcdpass'  => $request->xkcdpass,
            'channel'   => $request->channel
        ], true);
    }

    /**
     * Handles dynamic calls to the class.
     *
     * @return mixed
     */
    public function __call($name, $args)
    {
        if (is_null($this->client)) {
            return false;
        }
        return call_user_func_array([$this->client, $name], $args);
    }

    /**
     * Handles dynamic variable calls to the class.
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (is_null($this->client)) {
            return false;
        }
        return $this->client->{$name};
    }
}
