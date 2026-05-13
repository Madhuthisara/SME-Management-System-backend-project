<?php

namespace App\Providers;

use App\Repositories\AttributeOptionRepositoryInterface;
use App\Repositories\AttributeRepositoryInterface;
use App\Repositories\BaseRepositoryInterface;
use App\Repositories\BusinessRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Repositories\Eloquent\EloquentAttributeOptionRepository;
use App\Repositories\Eloquent\EloquentAttributeRepository;
use App\Repositories\Eloquent\EloquentBusinessRepository;
use App\Repositories\Eloquent\EloquentMaterialRepository;
use App\Repositories\Eloquent\EloquentMaterialStockRepository;
use App\Repositories\Eloquent\EloquentProductRepository;
use App\Repositories\Eloquent\EloquentProductTemplateRepository;
use App\Repositories\Eloquent\EloquentProductStockRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\MaterialRepositoryInterface;
use App\Repositories\MaterialStockRepositoryInterface;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\ProductTemplateRepositoryInterface;
use App\Repositories\ProductStockRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(AttributeRepositoryInterface::class, EloquentAttributeRepository::class);
        $this->app->bind(AttributeOptionRepositoryInterface::class, EloquentAttributeOptionRepository::class);
        $this->app->bind(MaterialRepositoryInterface::class, EloquentMaterialRepository::class);
        $this->app->bind(MaterialStockRepositoryInterface::class, EloquentMaterialStockRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(BusinessRepositoryInterface::class, EloquentBusinessRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(ProductTemplateRepositoryInterface::class, EloquentProductTemplateRepository::class);
        $this->app->bind(ProductStockRepositoryInterface::class, EloquentProductStockRepository::class);
        $this->app->bind( \App\Repositories\CategoryRepositoryInterface::class, \App\Repositories\Eloquent\EloquentCategoryRepository::class);
        $this->app->bind(\App\Repositories\CustomerRepositoryInterface::class, \App\Repositories\Eloquent\EloquentCustomerRepository::class);
        $this->app->bind(\App\Repositories\CartRepositoryInterface::class, \App\Repositories\Eloquent\EloquentCartRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
