<?php
namespace App;

class Category extends \Illuminate\Database\Eloquent\Model
{

    protected static $root = 1;

    protected $table = 'categories';

    protected $children = [];

    /**
     * All parent categories
     *
     * @return $this
     */
    public function parents()
    {
        return $this->belongsToMany('App\Category', 'category_category', 'child_id', 'parent_id');
    }

    /**
     * All child categories
     *
     * @return $this
     */
    public function children()
    {
        return $this->belongsToMany('App\Category', 'category_category', 'parent_id', 'child_id');
    }

    /**
     * All child documents
     *
     * @return $this
     */
    public function childrenDocuments()
    {
        return $this->hasMany('App\Document', 'parent_id');
    }

    /**
     * Get root category
     */
    public static function getRoot()
    {
        return self::find(self::$root);
    }

}