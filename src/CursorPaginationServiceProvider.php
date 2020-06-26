<?php

namespace DutchCodingCompany\CursorPagination;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class CursorPaginationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('cursor-pagination.php'),
            ], 'config');
        }

        $this->registerMacro();
    }

    /**
     * Create Macros for the Builders.
     */
    public function registerMacro()
    {
        $macro = function ($first, $currentCursor = null) {
            $queryOrders = isset($this->query) ? collect($this->query->orders) : collect($this->orders);
            $cursor = $currentCursor instanceof Cursor ? $currentCursor : Cursor::decode($currentCursor);

            if ($cursor->isPresent()) {
                $apply = function ($query, $queryOrders, $cursor) use (&$apply) {
                    $query->where(function ($query) use ($queryOrders, $cursor, $apply) {
                        $order = array_shift($queryOrders);
                        $column = Arr::get($order, 'column');
                        $direction = Arr::get($order, 'direction');

                        $value = $cursor->get($column);

                        $query->where($column, $direction === 'asc' ? '>' : '<', $value);

                        if (!empty($queryOrders)) {
                            $query->orWhere($column, $value);
                            $apply($query, $queryOrders, $cursor);
                        }
                    });
                };

                $apply($this, $queryOrders->toArray(), collect($cursor->getAfterCursor()));
            }

            $items = $this->limit($first + 1)->get();

            $hasAfter = false;
            // Check if there are items
            if ($items->count() === 0) {
                return new CursorPaginator($items, $currentCursor, $hasAfter);
            }
            // Check if there is a next page
            if ($items->count() > $first) {
                $items->pop();
                $hasAfter = true;
            }

            // Get last item and calculate afterCursor
            $lastItem = $items->last();
            $afterCursor = $queryOrders->mapWithKeys(function ($item) use ($lastItem) {
                $column = Arr::get($item, 'column');
                $value =  $lastItem->{$column};
                if ($value instanceof Carbon) {
                    $value = $value->toIso8601String();
                }
                return [$column => $value];
            })->toArray();
            $afterCursor = new Cursor($afterCursor);

            return new CursorPaginator($items, $afterCursor, $hasAfter);
        };

        // Register macros
        QueryBuilder::macro('cursorPaginate', $macro);
        EloquentBuilder::macro('cursorPaginate', $macro);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'cursor-pagination');

        // Register the main class to use with the facade
        $this->app->singleton('cursor-pagination', function () {
            return new CursorPaginator;
        });
    }
}
