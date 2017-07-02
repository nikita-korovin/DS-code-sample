<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class DocumentApiController extends Controller{

    /**
     * Get category children by id
     *
     * @param $id
     */
    public function getCategoryChildren($id)
    {
        return response()->json(Category::find($id)->children);
    }

    /**
     * Search for documents by title, text
     *
     * @param $term
     */
    public function search($term)
    {
        $q = \SphinxQL::query()->select()->from('document')->match(['title', 'text'], '*' . $term . '*',
            true)->execute();
        $documents = \SphinxQL::with($q)->get('App\Document');

        $data = [];
        foreach ($documents as $document) {
            $data[] = [
                'title' => $document->title,
                'content' => $document->text,
                'url' => '/docs_view/' . $document->id
            ];
        }
        return response()->json($data);
    }

    /**
     * Retrieve textual representation by id
     *
     * @param $vars
     */
    public function dataById()
    {
        $vars = request()->get('vars');
        foreach ($vars as $type => $typeArr) {
            foreach ($typeArr as $k => $item) {
                switch ($type) {
                    case 'party':
                        if (isset($item['id'])) {
                            $user = User::find($item['id']);
                            if ($user) {
                                $vars[$type][$k]['text'] = $user->first_name . ' ' . $user->last_name;
                            }
                        }
                        break;
                    case 'title':
                        $vars[$type][$k]['text'] = 'Contract';
                        break;
                }
            }
        }
        return response()->json($vars);
    }
    
}