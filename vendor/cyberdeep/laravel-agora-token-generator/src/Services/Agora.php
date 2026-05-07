<?php

namespace CyberDeep\LaravelAgoraTokenGenerator\Services;

use Exception;
use CyberDeep\LaravelAgoraTokenGenerator\Services\Token\RtcTokenBuilder;
use CyberDeep\LaravelAgoraTokenGenerator\Services\Token\RtcTokenBuilder2;

/**
 *
 */
class Agora
{
    /**
     * @var string|int
     */
    protected string|int $id;
    /**
     * @var string|null
     */
    protected string|null $channel = "agora";
    /**
     * @var string|null
     */
    protected string|null $uId = "";
    /**
     * @var bool
     */
    protected bool $audio = false;
    /**
     * @var bool
     */
    protected bool $join = false;

    /**
     * @param string|int $id
     * @return static
     */
    public static function make(string|int $id): static
    {
        return (new self())->id($id);
    }

    /**
     * @param string|int $id
     * @return $this
     */
    public function id(string|int $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param bool|null $join
     * @return $this
     */
    public function join(bool|null $join = true): static
    {
        $this->join = $join;
        return $this;
    }

    /**
     * @param bool|null $audio
     * @return $this
     */
    public function audioOnly(bool|null $audio = true): static
    {
        $this->audio = $audio;
        return $this;
    }

    /**
     * @param string $channel
     * @return $this
     */
    public function channel(string $channel): static
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @param string $uId
     * @return $this
     */
    public function uId(string $uId): static
    {
        $this->uId = $uId;
        return $this;
    }

    /**
     * @return string|void|null
     */
    public function token()
    {
        $appID = config('laravel-agora-token-generator.agora.app_id');
        $appCertificate = config('laravel-agora-token-generator.agora.app_certificate');
        $tokenBuilder = config('laravel-agora-token-generator.agora.token_builder', 'v1');

        if ($appID && $appCertificate) {
            // Use only the channel name without concatenating with ID to match Agora expectations
            $channelName = $this->channel;

            //Build a Time
            $expireTimeInSeconds = 3600;
            $currentTimestamp = now()->getTimestamp();
            $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

            if ($tokenBuilder === 'v2') {
                // Use RtcTokenBuilder2
                if ($this->join) {
                    $role = RtcTokenBuilder2::ROLE_SUBSCRIBER;
                } else {
                    $role = RtcTokenBuilder2::ROLE_PUBLISHER;
                }

                // Use buildTokenWithUid for both audio and video modes for consistency
                $token = RtcTokenBuilder2::buildTokenWithUid(
                    $appID,
                    $appCertificate,
                    $channelName,
                    $this->uId,
                    $role,
                    $privilegeExpiredTs,
                    $privilegeExpiredTs
                );
            } else {
                // Use RtcTokenBuilder (v1)
                if ($this->join) {
                    $role = RtcTokenBuilder::$roles['RoleSubscriber'];
                } else {
                    $role = RtcTokenBuilder::$roles['RolePublisher'];
                }

                $token = RtcTokenBuilder::build(
                    appID: $appID,
                    appCertificate: $appCertificate,
                    channelName: $channelName,
                    uid: $this->uId,
                    role: $role,
                    privilegeExpireTs: $privilegeExpiredTs,
                    type: $this->audio ? 'audio' : 'video'
                );
            }

            return $token;
        }

        abort(400, 'Sorry Agora API Key, or Certificate not Exists');
    }
}
