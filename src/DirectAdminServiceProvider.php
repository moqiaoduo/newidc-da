<?php

namespace NewIDC\DirectAdmin;

use Illuminate\Support\ServiceProvider;
use NewIDC\Plugin\Facade\PluginManager;

class DirectAdminServiceProvider extends ServiceProvider
{
    public function boot()
    {
        PluginManager::register(new Plugin());
    }
}