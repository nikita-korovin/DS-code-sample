<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $qins = \SphinxQL::query()->insert()->into('document');
            $qins->set([
                'gid' => $model->id,
                'id' => $model->id,
                'title' => $model->title,
                'text' => $model->text
            ])->execute();
        });

        static::updated(function ($model) {
            $qrep = \SphinxQL::query()->replace()->into('document');
            $qrep->set([
                'gid' => $model->id,
                'id' => $model->id,
                'title' => $model->title,
                'text' => $model->text
            ])->execute();
        });

        static::deleted(function ($model) {
            \SphinxQL::query()->delete()->from('document')->where('id', $model->id)->execute();
        });
    }


    /**
     * Extracts all parties from document using DOM and DOMXpath
     *
     * @return array
     */
    public function getParties()
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($this->text);
        $xpath = @new \DOMXPath($dom);

        $parties = [];
        foreach ($xpath->query('//input[@data-t="party"]') as $k => $element) {
            $parties[$element->getAttribute('data-id')] = 1;;
        }

        return $parties;
    }
}
