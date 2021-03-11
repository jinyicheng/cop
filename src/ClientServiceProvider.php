<?php
/**
 * COPClient
 * PHP version 7
 *
 * @category Class
 * @package  COP\Client
 * @author   jinyicheng
 * @link     https://github.com/jinyicheng/cop
 */

namespace jinyicheng\cop;
use Illuminate\Support\ServiceProvider;

/**
 * Class Client
 * @package jinyicheng\cop
 * @author   jinyicheng
 * @link     https://github.com/jinyicheng/cop
 */
class ClientServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // 发布配置文件
        $this->publishes([
            __DIR__.'/config/cop.php' => config_path('cop.php'),
        ]);
    }
}
