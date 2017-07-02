<?php

namespace App\Providers;

class SocialiteManager extends \Laravel\Socialite\SocialiteManager{

    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function createFacebookDriver()
    {
        $config = $this->app['config']['services.facebook'];

        return $this->buildProvider(
            'App\Providers\FacebookProvider', $config
        );

    }

}