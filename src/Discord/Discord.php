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
        if (is_null($token)) {
            $request = Guzzle::post('auth/login', [
                'email'     => $email,
                'password'  => $password
            ], true);

            $token = $request->token;
        }

        @define("DISCORD_TOKEN", $token);

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
