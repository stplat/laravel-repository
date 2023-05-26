<?php

namespace Stsp\LaravelRepository;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;

/**
 * Class Repository
 * @package App\Repositories
 */
abstract class Repository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepositories constructor.
     */
    public function __construct()
    {
        $this->model = app($this->getModelClass());
    }

    /**
     * @return Model
     */
    abstract protected function getModelClass();

    /**
     * @return Model
     */
    protected function startCondition(): Model
    {
        return clone $this->model;
    }

    /**
     * Get the name of the table from the model
     *
     * @return string
     */
    protected function getTableName(): string
    {
        return $this->startCondition()->getTable();
    }

    /**
     * Common method for find item by id
     *
     * @param int $id
     * @return ?Model
     */
    public function findItemById(int $id): ?Model
    {
        return $this->startCondition()->find($id);
    }

    /**
     * Find item by key (from 1C)
     *
     * @param int $key
     * @return Collection
     */
    public function findItemByKey(string $key): Collection
    {
        return collect($this->startCondition()->where('key', $key)->first());
    }

    /**
     * Common method for find all items
     *
     * @return Collection
     */
    public function findAllItems(): Collection
    {
        return $this->startCondition()->get();
    }

    /**
     * Redefined method for fing items by filters
     *
     * @param object $data
     * @return Collection
     */
    public function findItemsByFilters(object $data): Collection
    {
        return $this->startCondition()->get();
    }

    /**
     * Paginate from the model
     *
     * @param  array $columns
     * @param  string $columnOrder
     * @param  int $page
     * @param  int $limit
     * @param  string $orderBy
     * @param  callable $callback
     * @param  bool $queryBuilder
     * @return Illuminate\Support\Collection
     */
    protected function paginate(array $columns = [], string $columnOrder = null, ?int $page = 1, ?int $limit = 5, ?string $orderBy = "ASC", callable $callback = null, bool $queryBuilder = false): Collection
    {
        $query = $this->startCondition()->select($columns);

        $page = $page ?? 1;
        $limit = $limit ?? 5;
        $orderBy = $orderBy ?? 'ASC';

        if ($callback) {
            $query = $callback($query);
        }

        $count = $query->count();
        $page = ($page - 1) * $limit < $count ? $page : $page - 1;
        $skip = ($page - 1) * $limit;


        if ($skip > 0) {
            $query = $query->skip($skip);
        }

        if ($columnOrder) {
            $tableName = $this->getTableName();

            if (!Schema::hasColumn($tableName, $columnOrder)) {
                throw new \Exception("The column - \"$columnOrder\" does not exist in the table - \"$tableName\"");
            }

            $query = $query->orderBy($columnOrder, $orderBy);
        }

        if ($limit) {
            $query = $query->take($limit);
        }

        return collect([
            'total' => $count,
            'data' => $queryBuilder ? $query : $query->get(),
            'limit' => $limit,
            'order' => $orderBy,
            'page' => $page,
            'column_order' => $columnOrder,
        ]);
    }

    /**
     * Sampling between amounts
     *
     * @param  Builder $query
     * @param  null|array $data
     * @param  string $field
     * @return Builder
     */
    protected function whereBetween(Builder $query, ?array $data = null, string $field = ''): Builder
    {
        if (!isset($data) && !is_array($data)) return $query;

        $tableName = $this->getTableName();

        if (array_key_exists('min', $data) && $data['min']) {
            $query = $query->where("$tableName.$field", '>=', $data['min']);
        }
        if (array_key_exists('max', $data) && $data['max']) {
            $query = $query->where("$tableName.$field", '<=', $data['max']);
        }

        return $query;
    }

    /**
     * Sampling between dates
     *
     * @param  Builder $query
     * @param  null|array $data
     * @param  string $field
     * @return Builder
     */
    protected function whereBetweenDate(Builder $query, ?array $data = null, string $field = ''): Builder
    {
        if (!isset($data) && !is_array($data)) return $query;
        $tableName = $this->getTableName();

        if (array_key_exists('from', $data) && $data['from']) {
            $query = $query->whereDate("$tableName.$field", '>=', $data['from']);
        }
        if (array_key_exists('to', $data) && $data['to']) {
            $query = $query->whereDate("$tableName.$field", '<=', $data['to']);
        }

        return $query;
    }

    /**
     * Find part of the occurrence of a string
     *
     * @param  Builder $query
     * @param  null|string $data
     * @param  string $field
     * @return Builder
     */
    protected function whereText(Builder $query, ?string $data = null, string $field = ''): Builder
    {
        if (!isset($data)) return $query;
        $tableName = $this->getTableName();

        $query = $query->where("$tableName.$field", 'ilike', '%' . $data . '%');

        return $query;
    }

    /**
     * Paginate from the model
     *
     * @param  array $ids
     * @param  array $columnNames
     * @param  array $columns
     * @return Illuminate\Support\Collection
     */
    protected function export(array $ids = [], array $columnNames = ['id'], array $columns = []): Collection
    {
        if (count($columnNames)) {
            $columns = collect($columns)
                ->filter(function ($item, $key) use ($columnNames) {
                    return in_array($key, $columnNames);
                })->toArray();
        }


        $tableName = $this->getTableName();

        $query = $this->startCondition()
            ->select($columns);

        if (count($ids)) {
            $query = $query->whereIn($tableName . '.id', $ids);
        }

        return $query->get();
    }

    /**
     * Record handler in the database
     *
     * @param  int $id
     * @return bool
     */
    public function recordHandler(int $id, callable $callback = null): bool
    {
        try {
            $model = $this->startCondition()::findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception->getMessage());
            return false;
        }

        try {
            return $callback($model);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return false;
        }
    }

    /**
     * Save record in the database
     *
     * @param  mixed $data
     * @return bool
     */
    public function saveHandler(callable $callback): bool
    {
        try {
            $model = app($this->getModelClass());
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return false;
        }

        try {
            return $callback($model);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return false;
        }
    }

    /**
     * Save image to storage
     *
     * @param  UploadedFile $image
     * @return string|bool
     */
    public function saveImage(UploadedFile $image): string|bool
    {
        try {
            return Storage::disk('public')->putFile('images', $image);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return false;
        }
    }

    /**
     * Update image to storage
     *
     * @param  string $imagePath
     * @param  UploadedFile $image
     * @return bool|string
     */
    public function updateImage(string $imagePath, UploadedFile $image): bool|string
    {
        try {
            $this->deleteImage($imagePath);
            return Storage::disk('public')->putFile('images', $image);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return false;
        }
    }

    /**
     * Delete image to storage
     *
     * @param  string $imagePath
     * @return bool
     */
    public function deleteImage(string $imagePath): bool
    {
        try {
            $fileName = Str::replaceFirst(asset('storage'), '', $imagePath);
            return Storage::disk('public')->delete($fileName);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return false;
        }
    }

    /**
     * Mass creation
     *
     * @param  mixed $data
     * @return bool
     */
    public function insert(array $data): bool
    {
        return $this->startCondition()->insert($data);
    }

    /**
     * Save item data.
     *
     * @param object $data
     * @return bool
     */
    public function save(object $data): bool
    {
        return true;
    }

    /**
     * Update item data.
     *
     * @param object $data
     * @return bool
     */
    public function update(object $data): bool
    {
        return true;
    }

    /**
     * Delete item
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return true;
    }

    /**
     * Truncate table
     */
    public function truncate()
    {
        $this->startCondition()->truncate();
    }
}
