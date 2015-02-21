<?php

require_once __DIR__ . '/../vendor/autoload.php';


/**
 * Class Document
 * 状態を持つオブジェクトのサンプルとしてドキュメントが使われています。
 * 管理したい状態を持つオブジェクトはFinite\StatefulInterfaceをimplementsします。
 *
 * StatefulInterfaceはgetFiniteState()とsetFiniteState()を実装することを要求しているので、
 * 状態管理したいプロパティをset、getできるようしておいてあげます。
 */
// Implement your document class
class Document implements Finite\StatefulInterface
{
    private $state;

    public function getFiniteState()
    {
        return $this->state;
    }

    public function setFiniteState($state)
    {
        $this->state = $state;
    }
}

// Configure your graph
$document     = new Document;

/**
 * ドキュメントインスタンスを渡してステートマシンのインスタンスを作成します。
 * この渡したものに対してステートマシンは状態管理を行います。
 */
$stateMachine = new Finite\StateMachine\StateMachine($document);

/**
 * ローダーを作成します。
 * ここで状態遷移のルールを決めていきます。
 * 下記の例だとドキュメントに対してのルールを定めていっています。
 * ローダーには以下の情報を設定することができます。
 *   * class
 *     * 設定したいクラス名をいれます。
 *   * states
 *     * 状態の設定をします。
 *     * type
 *       * 初期状態、通常状態、終了状態の3つから選択します。
 *       * デフォルトは通常状態です。
 *     * properties
 *       * 状態に持たせたい設定情報があれば、連想配列形式で自由に持たせる事ができます。
 *   * transitions
 *     * states間の遷移を設定します。
 *     * どの状態からどの状態には遷移可能、というのを記述します。
 *   *
 *
 * "draft"というstateは「初期状態であり、削除可能かつ編集可能」という設定になっています。
 */
$loader       = new Finite\Loader\ArrayLoader(array(
    'class'  => 'Document',
    'states'  => array(
        'draft' => array(
            'type'       => Finite\State\StateInterface::TYPE_INITIAL,
            'properties' => array('deletable' => true, 'editable' => true, 'comment' => '下書き'),
        ),
        'proposed' => array(
            'type'       => Finite\State\StateInterface::TYPE_NORMAL,
            'properties' => array('comment' => '提出済'),
        ),
        'accepted' => array(
            'type'       => Finite\State\StateInterface::TYPE_FINAL,
            'properties' => array('printable' => true),
        )
    ),
    'transitions' => array(
        'propose' => array('from' => array('draft'), 'to' => 'proposed'),
        'accept'  => array('from' => array('proposed'), 'to' => 'accepted'),
        'reject'  => array('from' => array('proposed'), 'to' => 'draft'),
    ),
));


/**
 * 上記で作成したローダーで、ステートマシンに設定をロードします。
 * これでステートマシンがコンストラクタ引数にて受け取ったインスタンスに対してどのように状態を管理していくかを
 * 理解することができるようになります。
 */
$loader->load($stateMachine);

/**
 * ステートマシンの初期化を行います。
 * ここでローダーのルールに従って状態をもつインスタンスの初期化を行います。
 * 同時に、ステートマシンも「現時点での状態」を知ります。
 * 上記の例だと初期化するとtypeがINITIALである"draft"がセットされることになります。
 */
$stateMachine->initialize();


// Working with workflow

// Current state

var_dump($stateMachine->getCurrentState()->getName()); // 初期状態なので"draft"が返ります。
/**
string(5) "draft"
 */
var_dump($stateMachine->getCurrentState()->getProperties()); // 初期状態のプロパティが返ります。
/**
array(2) {
["deletable"]=>
bool(true)
["editable"]=>
bool(true)
["comment"]=>
string(9) "下書き"
} */
var_dump($stateMachine->getCurrentState()->has('deletable')); // draftが'deletable'プロパティを持つかを返します。持っているのでtrue
/**
 * bool(true)
 */
var_dump($stateMachine->getCurrentState()->has('printable')); // draftが'printable'プロパティを持つかを返します。持っていないのでfalse
/**
 * bool(false)
 */

var_dump($stateMachine->getCurrentState()->get('comment')); // draftがもつ'comment'プロパティを返します。'下書き'が返ります
/**
 * string(9) "下書き"
 */

var_dump($stateMachine->getCurrentState()->get('printable')); // draftがもつ'printable'プロパティを返します。持っていない場合はNULL
/**
 * NULL
 */

// Available transitions
var_dump($stateMachine->getCurrentState()->getTransitions()); // draftの持っているtransitionsを返します。
/**
array(1) {
    [0]=>
  string(7) "propose"
}
 */

var_dump($stateMachine->can('propose')); // 'propose'トランジションを適用可能かを判定します。draftはproposeトランジションの開始状態なのでtrue
/**
 * bool(true)
 */

var_dump($stateMachine->can('accept')); // 'accept'トランジションを適用可能かを判定します。draftはproposeトランジションの開始状態ではないのでfalse
/**
 * bool(false)
 */

// Apply transitions
try {
    $stateMachine->apply('accept'); // 適用可能でないトランジションを適用しようとするとStateExceptionが投げられます。
} catch (\Finite\Exception\StateException $e) {
    echo $e->getMessage(), "\n";
    /**
     * The "accept" transition can not be applied to the "draft" state of object "Document" with graph "default".
     */
}

// Applying a transition
$stateMachine->apply('propose'); // 適用すると状態が遷移します。

var_dump($stateMachine->getCurrentState()->getName()); // 状態が遷移したのでdraftからproposedに変化しています。
/**
 * string(8) "proposed"
 */
var_dump($document->getFiniteState()); // 状態が遷移したのでdraftからproposedに変化しています。
/**
 * string(8) "proposed"
 */
var_dump($stateMachine->getCurrentState()->getProperties()); // 取得できるプロパティもproposedのものに変化しています。
/**
array(1) {
["comment"]=>
string(9) "提出済"
}
 */
