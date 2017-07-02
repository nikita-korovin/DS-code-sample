<?php

namespace App;

use App\Notifications\DocumentParty;
use App\Notifications\PartnerRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class UserDocument extends Model
{
    protected $table = 'user_documents';

    const STATUS_NONE = 0;
    const STATUS_SIGNED = 1;

    const PERMISSION_NONE = 0;
    const PERMISSION_READ = 1;
    const PERMISSION_WRITE = 2;
    const PERMISSION_OWNER = 3;

    /**
     * Add triggers to model manipulation
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $qins = \SphinxQL::query()->insert()->into('userdocument');
            $qins->set(['gid' => $model->id, 'id' => $model->id, 'changes' => (string)$model->changes])->execute();
        });

        static::updated(function ($model) {
            $qrep = \SphinxQL::query()->replace()->into('userdocument');
            $qrep->set(['gid' => $model->id, 'id' => $model->id, 'changes' => (string)$model->changes])->execute();
        });

        static::deleted(function ($model) {
            \SphinxQL::query()->delete()->from('userdocument')->where('id', $model->id)->execute();
        });
    }

    public function document()
    {
        return $this->belongsTo('App\Document', 'doc_id', 'id');
    }

    public function signed()
    {
        return $this->hasMany('App\UserDocumentSigned', 'doc_id', 'id');
    }

    /**
     * Returns true if document is signed by all parties
     * @return bool
     */
    public function isSigned()
    {

        $changes = $this->getChanges();
        $parties = $this->document->getParties();

        if (!isset($changes['variables']['party'])) {
            return false;
        }

        foreach ($parties as $k => $val) {
            if (!array_key_exists($k, $changes['variables']['party'])) {
                return false;
            } else {
                if (!UserDocumentSigned::where([
                    'user_id' => $changes['variables']['party'][$k],
                    'doc_id' => $this->id,
                    'status' => self::STATUS_SIGNED
                ])->first()
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return string
     *
     * Get html representation of the document wiwh all changes
     */
    public function getHTML()
    {
        $changes = $this->getChanges();

        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">' . $this->document->text);
        if ($changes) {
            if (isset($changes['paragraph'])) {
                foreach ($changes['paragraph'] as $action => $data) {
                    foreach ($data as $k) {
                        switch ($action) {
                            case 'add':
                                $h3 = new \DOMElement('h3');
                                $h3->textContent = 'Paragraph ' . $k;

                                $paragraphAttr = new \DOMAttr('data-n', $k);

                                $textElement = new \DOMElement('div');

                                $dummyTextElement = new \DOMText('New paragraph text');

                                $justSpan = new \DOMElement('span');

                                $paragraphElement = new \DOMElement('div');
                                $paragraph = $doc->getElementsByTagName('section')[0]->appendChild($paragraphElement);
                                $paragraph->appendChild($paragraphAttr);
                                $paragraph->appendChild($h3);
                                $paragraph->setAttribute('class', 'add');
                                $paragraph->setAttribute('data-p', $k);
                                $textNode = $paragraph->appendChild($textElement);
                                $spanNode = $textNode->appendChild($justSpan);
                                $spanNode->appendChild($dummyTextElement);
                                break;
                            case 'remove':
                                $xpath = new \DOMXpath($doc);
                                $elements = $xpath->query("/html/body/section/div");
                                foreach ($elements as $element) {
                                    if ($element->getAttribute('data-p') == $k) {
                                        $element->setAttribute('class', 'hide');
                                    }
                                }
                                break;
                        }
                    }

                }
            }


            if (isset($changes['paragraph']['edit'])) {
                foreach ($changes['paragraph']['edit'] as $key => $data) {
                    $xpath = new \DOMXpath($doc);
                    $elements = $xpath->query("/html/body/section/div");
                    foreach ($elements as $element) {
                        if ($element->getAttribute('data-p') == $key) {
                            $tempDoc = new \DOMDocument();
                            @$tempDoc->loadHTML('<div>' . $data . '</div>');
                            $fragment = $doc->importNode($tempDoc->documentElement, true);
                            foreach ($element->childNodes as $k => $node) {
                                if (isset($node->tagName) && $node->tagName === 'div') {
                                    $element->removeChild($node);
                                }
                            }
                            $element->appendChild($fragment);
                            $element->setAttribute('class', $element->getAttribute('class') . ' edit');
                        }
                    }
                }
            }

            if (isset($changes['variables'])) {
                foreach ($doc->getElementsByTagName('input') as $item) {
                    if (isset($changes['variables'][$item->getAttribute('data-t')][(int)$item->getAttribute('data-id')])) {
                        $item->setAttribute('value',
                            $changes['variables'][$item->getAttribute('data-t')][(int)$item->getAttribute('data-id')]);
                    }
                }
            }

        }

        return $doc->saveHTML();
    }

    /**
     * Checks if var type is required to fill
     *
     * @return array
     */
    protected function isRequiredType($type)
    {
        return in_array($type, [
            'party',
            'amount',
            'date'
        ]);
    }

    /**
     * Get all document variables
     *
     * @return array
     */
    protected function getVars()
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($this->getHTML());
        $xpath = @new \DOMXPath($dom);

        $vars = [];
        foreach ($xpath->query('//input[@data-t]') as $k => $element) {
            if ($element->getAttribute('data-id')) {
                $vars[$element->getAttribute('data-t')][$element->getAttribute('data-id')] = 1;
            }
        }

        return $vars;
    }

    /**
     * Returns true if all required variables are filled
     *
     * @return bool
     */
    public function isFilled()
    {
        $changes = $this->getChanges();
        $vars = $this->getVars();

        foreach ($vars as $type => $var) {
            if ($this->isRequiredType($type)) {
                foreach ($var as $k => $val) {
                    if (!isset($changes['variables'][$type]) || !array_key_exists($k, $changes['variables'][$type])) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Key information about document
     * @return array
     */
    public function status()
    {
        return [
            'isFilled' => $this->isFilled(),
            'isSignedByMe' => $this->isSignedByMe(),
            'isSigned' => $this->isSigned(),
            'isSignedByPartner' => $this->isSignedByPartner(),
            'relation' => UserDocumentSigned::where(['doc_id' => $this->id, 'user_id' => \Auth::user()->id])->first()
        ];
    }

    /**
     * Returns true if document cannot be modified
     * @return bool
     */
    public function isLocked()
    {
        $status = $this->status();
        return $status['isSignedByMe'] || $status['isSignedByPartner'] || !$status['isFilled'];
    }

    /**
     * Returns true if document is signed by current user
     * @return bool
     */
    public function isSignedByMe()
    {
        return (bool)UserDocumentSigned::where([
            'user_id' => \Auth::user()->id,
            'doc_id' => $this->id,
            'status' => self::STATUS_SIGNED
        ])->first();
    }

    /**
     * Returns true if document is signed by any other partner
     * @return bool
     */
    public function isSignedByPartner()
    {
        return (bool)UserDocumentSigned::where([
            'doc_id' => $this->id,
            'status' => self::STATUS_SIGNED
        ])->where('user_id', '!=', \Auth::user()->id)->first();
    }

    /**
     * Returns all changes made to original document
     * including variables, article manipulation, etc
     * @return array
     */
    public function getChanges()
    {
        if (!is_array($this->changes)) {
            return json_decode($this->changes, true);
        } else {
            return $this->changes;
        }
    }

    /**
     * Signs a document by current user
     * @return array
     */
    public function sign()
    {
        if (\Auth::user()->verified === User::VERIFICATION_STATUS_NONE) {
            \Session::set('action_after_verify', ['sign' => $this->id]);
            return [
                'status' => -1,
                'message' => 'You identity is not yet verified'
            ];
        }

        if (\Auth::user()->verified === User::VERIFICATION_STATUS_PENDING) {
            \Session::set('action_after_verify', ['sign' => $this->id]);
            return [
                'status' => 0,
                'message' => 'You identity is not yet verified'
            ];
        }

        if ($ud = UserDocumentSigned::where(['user_id' => \Auth::user()->id, 'doc_id' => $this->id])->first()) {
            if ($ud->status === self::STATUS_SIGNED) {
                return [
                    'status' => 1,
                    'message' => 'Document has been already signed'
                ];
            }
            $res = \DB::table('user_document_users')
                ->where(['doc_id' => $this->id, 'user_id' => \Auth::user()->id])
                ->update(['status' => self::STATUS_SIGNED]);
            if ($res) {
                return [
                    'status' => 1,
                    'message' => 'Document was successfully signed'
                ];
            }
        } else {
            return [
                'status' => 0,
                'message' => 'Could not sign document'
            ];
        }

    }

    /**
     * Store user changes
     */
    public function setChanges($changes)
    {
        $this->_processChanges($changes);
        if (is_array($changes)) {
            $this->changes = json_encode($changes);
        }
        $this->setUsers($changes['variables']['party']);
    }

    public function setUsers(array $users)
    {
        foreach ($users as $id) {
            if (!DB::table('user_document_users')->where(['doc_id' => $this->id, 'user_id' => $id])->first()) {
                UserDocumentSigned::create([
                    'doc_id' => $this->id,
                    'user_id' => $id,
                    'status' => self::STATUS_NONE,
                    'permission' => (($this->user_id == $id) ? self::PERMISSION_OWNER : self::PERMISSION_READ)
                ]);
            }
            // !important! delete or set status NONE to all users not mentioned in $users array!
        }
    }

    /**
     * See in any changes need additional actions
     *
     * @param $changes
     */
    protected function _processChanges($changes)
    {
        if (isset($changes['variables'])) {
            foreach ($changes['variables'] as $type => $typeArray) {
                foreach ($typeArray as $id) {
                    if ($config = $this->_notifiable($type, $id)) {
                        $notifiable = $config['notifiable'];
                        $notification = $config['notification'];
                        $notifiable_item = $notifiable::find($id);
                        $notifiable_item->notify($notification);
                    }
                }
            }
        }
    }

    /**
     * Check if a change must be notified
     *
     * @param $change_type
     * @return array
     */
    protected function _notifiable($change_type, $id)
    {

        $notifiable = [
            'party' => [
                'notifiable' => User::class,
                'notification' => new DocumentParty($this->id),
                'exceptions' => [\Auth::user()->id]
            ]
        ];
        return (
                (array_key_exists($change_type, $notifiable) && 
                !in_array($id, $notifiable[$change_type]['exceptions']) && 
                !$this->_isNotifyCooldown($id, get_class($notifiable[$change_type]['notification']))
            ) ? $notifiable[$change_type] : []
        );
    }

    /**
     * determine if notification is on cooldown
     *
     * @param $to
     * @param $type
     * @return bool
     */
    protected function _isNotifyCooldown($to, $type)
    {

        $last_notification = DB::table('notifications')
            ->where('notifiable_id', '=', $to)
            ->where('type', '=', $type)
            ->where('data', 'LIKE', '%"from":' . \Auth::user()->id . '%')
            ->where('data', 'LIKE', '%"doc_id":' . $this->id . '%')
            ->orderBy('created_at', 'desc')
            ->limit(1)
            ->first();

        return (bool)$last_notification;
    }

}
