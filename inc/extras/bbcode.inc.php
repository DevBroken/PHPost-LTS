<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

require_once dirname(__DIR__, 1) . '/libs/JBBCode/Parser.php';
require_once dirname(__DIR__, 1) . '/libs/JBBCode/definitions/Video.php';
require_once dirname(__DIR__, 1) . '/libs/JBBCode/validators/ColorValidator.php';
require_once dirname(__DIR__, 1) . '/libs/JBBCode/validators/UrlValidator.php';
require_once dirname(__DIR__, 1) . '/libs/JBBCode/validators/AlignValidator.php';
require_once dirname(__DIR__, 1) . '/libs/JBBCode/validators/SizeValidator.php';
require_once dirname(__DIR__, 1) . '/libs/JBBCode/validators/ImgValidator.php';
require_once dirname(__DIR__, 1) . '/libs/JBBCode/validators/FontValidator.php';

use DevBroken\Utils\Misc\Sources;

/**
 * Clase responsable de la conversión de texto en formato
 * de marcado BBCode a XHTML para creración de contenido
 * usado en posts, fotos, comentarios, etc.
 *
 * Extiende de la clase jBBCode para facilitar el uso de
 * todas las herramientas disponibles para la conversión
 * además de su excelente seguridad para el script.
 *
 * @author Kmario19 y PHPost.
 */
class BBCode {

    /**
     * String texto BBcode
     */
    private string $text;

    /**
     * BBCodes permitidos
     */
    private array $restriction;

    /**
     * jBBCode
     */
    private object $parser;

    protected Sources $Sources;

    public function __construct(Sources $Sources) {
        $this->Sources = $Sources;
        $this->restriction = [];
        $this->parser = new JBBCode\Parser();
    }

    public function bbcodeAllow(): array {
        return ['url', 'b', 'i', 'u', 's', 'font', 'size', 'color', 'center', 'align', 'spoiler', 'code', 'quote', 'video','sub', 'sup', 'image', 'item', 'list', 'kbd', 'table', 'thead', 'tbody', 'th', 'td', 'tr', 'notice', 'info', 'warning', 'error', 'success', 'highlight', 'mention', 'badge', 'diff', 'divider'];
    }

    /**
     * Prepara el texto con el que se trabajará
     *
     * @param string $text  texto a parsear
     */
    public function setText(string $text) {
        $this->text = $text;
        $this->unclosedTags();
    }

    /**
     * Modificar restricciones de BBCode
     *
     * @param array $array  lista de tags permitidos
     */
    public function setRestriction(array $array) {
        $this->restriction = $array;
        $this->addBBcodes();
    }

    /**
     * Elimina etiquetas BBcode y deja el texto plano
     *
     * @return string
     */
    public function getAsText() {
        $this->parser->parse($this->text);
        $this->text = $this->parser->getAsText();
        $this->delExtraTags();
        return htmlspecialchars_decode(strip_tags($this->text));
    }

    /**
     * Obtiene el texto en HTML
     *
     * @return string
     */
    public function getAsHtml() {
        $this->parser->parse($this->text);
        $this->text = $this->parser->getAsHtml();
        $this->setExtraTags();
        return nl2br($this->text);
    }

    /**
     * Fix para tags que no tienen etiqueta de cierre
     * y para tags de YouTube de la versión anterior
     */
    private function unclosedTags() {
        $this->text = preg_replace("/[\.com]+\/v\//i", ".com/watch?v=", $this->text);
        $this->text = preg_replace("/\[swf=(http|https)?(\:\/\/)?www\.youtube\.com\/watch\?v([A-z0-9=\-]+?)\]/i", "[video]$1$2www.youtube.com/watch?v$3[/video]", $this->text);

        $this->text = preg_replace("/\[img\=(.+?)\]/i", "[img]$1[/img]", $this->text);
        $this->text = preg_replace("/\[swf\=(.+?)\]/i", "[swf]$1[/swf]", $this->text);

        $this->text = str_replace('&#039;', '\'', $this->text);
    }

    /**
     * Parsea tag de línea de división
     * saltos de línea
     */
    private function setExtraTags() {
        if (in_array('hr', $this->restriction)) {
            $this->text = str_replace('[hr]', '<hr />', $this->text);
        }

        $this->text = str_replace('\n', '<br />', $this->text);
    }

    /**
     * Elimina tag de línea de división
     * saltos de línea
     * espacios vacíos
     */
    private function delExtraTags() {
        $this->text = str_replace(array('[hr]', '\n', '\r'), ' ', $this->text);
        $this->text = preg_replace('!\s+!', ' ', $this->text);
        $this->text = preg_replace('/((http|https|www)[^\s]+)/', '', $this->text);
    }

    /**
     * Agrega y valida los BBcodes a parsear.
     *
     * Si el bbcode se encuentra en el array de la restricción, será permitido.
     * Si no es válido lo que se pasa por parametro o contenido se verá el bbcode
     * sin ser parseado. Ejemplo: [a]no es link[/a] => [a]no es link[/a]
     *
     * Cada bbcode tiene su configuración de:
     *
     * TagName: Nombre del tag de bbcode.
     * Replace: En qué formrato HTML se reemplazará.
     *          Usar como variables de referencia {option} y {param}.
     * UseOption: Si el tag usa parámetro ({option}).
     * ParseContent: Si el contenido del tag también será parseado.
     * NestLimit: Límite de cuantas veces se repite este tag en su contenido (incluyendose).
     * OptionValidator: Clase con la cual se valida lo que se pasa por parámetro.
     * BodyValidator: Clase con la cual se valida lo que se pasa como contenido del tag.
     */
    public function addBBcodes() {
        $urlValidator   = new \JBBCode\validators\UrlValidator();
        $colorValidator = new \JBBCode\validators\ColorValidator();
        $sizeValidator  = new \JBBCode\validators\SizeValidator();
        $alignValidator = new \JBBCode\validators\AlignValidator();
        $imgValidator   = new \JBBCode\validators\ImgValidator();
		$fontValidator  = new \JBBCode\validators\FontValidator();

        $tagCodes = array(
            array('tag' => 'b', 'replace' => '<strong>{param}</strong>'),
            array('tag' => 'i', 'replace' => '<i>{param}</i>'),
            array('tag' => 'u', 'replace' => '<u>{param}</u>'),
            array('tag' => 's', 'replace' => '<s>{param}</s>'),
            array('tag' => 'sub', 'replace' => '<sub>{param}</sub>'),
            array('tag' => 'sup', 'replace' => '<sup>{param}</sup>'),
            array('tag' => 'table', 'replace' => '<table class="bbctab"><tbody>{param}</tbody></table>'),
            array('tag' => 'tr', 'replace' => '<tr>{param}</tr>'),
            array('tag' => 'td', 'replace' => '<td>{param}</td>'),
            array('tag' => 'ul', 'replace' => '<ul>{param}</ul>'),
            array('tag' => 'li', 'replace' => '<li>{param}</li>'),
            array('tag' => 'ol', 'replace' => '<ol>{param}</ol>'),
            array('tag' => 'url', 'replace' => '<a href="{param}" target="_blank">{param}</a>', 'parse' => false, 'validParam' => $urlValidator),
            array('tag' => 'url', 'replace' => '<a href="{option}" target="_blank">{param}</a>', 'option' => true, 'validOption' => $urlValidator),
            array('tag' => 'img', 'replace' => '<img src="{param}" />', 'parse' => false, 'validParam' => $imgValidator),
            array('tag' => 'color', 'replace' => '<span style="color: {option}">{param}</span>', 'option' => true, 'validOption' => $colorValidator),
            array('tag' => 'size', 'replace' => '<span style="font-size: {option}pt; line-height: {option}pt">{param}</span>', 'option' => true, 'validOption' => $sizeValidator),
            array('tag' => 'align', 'replace' => '<div style="text-align: {option}">{param}</div>', 'option' => true, 'validOption' => $alignValidator),
            array('tag' => 'font', 'replace' => '<span style="font-family: {option}">{param}</span>', 'option' => true, 'validOption' =>$fontValidator),
            array('tag' => 'code', 'replace' => '<pre class="code">{param}</pre>', 'parse' => false, 'limit' => 1),
            array('tag' => 'spoiler', 'replace' => '<div class="spoiler"><div class="title"><a href="#" onclick="spoiler($(this)); return false;">Spoiler:</a></div><div class="body">{param}</div></div>'),
            array('tag' => 'quote', 'replace' => '<blockquote><div class="cita"><strong>Cita:</strong></div><div class="citacuerpo"><p>{param}</p></div></blockquote>'),
            array('tag' => 'quote', 'replace' => '<blockquote><div class="cita"><strong>{option} dijo:</strong></div><div class="citacuerpo"><p>{param}</p></div></blockquote>', 'option' => true),
            array('tag' => 'notice', 'replace' => '<div class="bbcmsg notice">{param}</div>'),
            array('tag' => 'info', 'replace' => '<div class="bbcmsg info">{param}</div>'),
            array('tag' => 'warning', 'replace' => '<div class="bbcmsg warning">{param}</div>'),
            array('tag' => 'error', 'replace' => '<div class="bbcmsg error">{param}</div>'),
            array('tag' => 'success', 'replace' => '<div class="bbcmsg success">{param}</div>')
        );

        foreach ($tagCodes as $bbcode) {
            if (in_array($bbcode['tag'], $this->restriction) || !$this->restriction) {
                $tag = $bbcode['tag'];
                $replace = $bbcode['replace'];
                $option = isset($bbcode['option']) ? $bbcode['option'] : false;
                $parse = isset($bbcode['parse']) ? $bbcode['parse'] : true;
                $limit = isset($bbcode['limit']) ? $bbcode['limit'] : -1;
                $validOption = isset($bbcode['validOption']) ? $bbcode['validOption'] : null;
                $validParam = isset($bbcode['validParam']) ? $bbcode['validParam'] : null;

                $this->parser->addBBCode($tag, $replace, $option, $parse, $limit, $validOption, $validParam);
            }
        }
        // Tag de video independiente
        if (in_array('video', $this->restriction) || !$this->restriction) {
            $this->parser->addCodeDefinition(new Video());
        }
    }
    
    /**
     * @name parseMentions
     * @access public
     * @param string
     * @return string
     * @info PONE LOS LINKS A LOS MENCIONADOS
     */
    public function parseMentions() {
        global $tsUser;
        $this->text = $tsUser->setMenciones($this->text);
        $this->text = substr($this->text, 0, -1);
    }
    
    /**
     * @name parseSmiles()
     * @access public
     * @description Convierte los Smiles
     */
	public function parseSmiles() {
		// SMILEYS
		$bbcode = [];
		$html = [];
		$smiles = [
            ":)" => "001.png",
            ":D" => "002.png",
            ";)" => "003.gif",
            ":O" => "004.png",
            "(H)" => "006.png",
            ":P" => "104.png",
            "8o|" => "049.png",
            ":S" => "009.png",
            ":$" => "008.png",
            ":(" => "010.png",
            ":'(" => "011.gif",
            ":|" => "012.png",
            "(6)" => "013.png",
            "8-|" => "050.png",
            ":-/" => "083.png",
            "^o)" => "051.png",
            "(A)" => "014.png",
            ":[" => "043.png",
            ":-#" => "048.png",
            ":-*" => "052.png",
            "+o(" => "053.png",
            "(brb)" => "066.gif",
            ":^)" => "072.gif",
            "*-)" => "073.gif",
            "<o)" => "075.gif",
            "8-)" => "076.gif",
            "|-)" => "078.gif",
            ";-/" => "082.png",
            "(jk)" => "084.png",
            "(j)" => "086.png",
            "(V)" => "087.png",
            "(lol)" => "089.gif",
            "(xD)" => "090.png",
            ":8)" => "088.png",
            "(ff)" => "091.gif",
            "(fm)" => "092.gif",
            ":'|" => "093.gif",
            ":]" => "094.gif",
            ":}" => "095.png",
            "(BOO)" => "096.png",
            "*|" => "097.gif",
            "*\\" => "098.png",
            "(wm)" => "100.png",
            "(xo)" => "101.gif",
            "(l)" => "015.png",
            "(u)" => "016.png",
            "(@)" => "018.png",
            "(&)" => "019.png",
            "(S)" => "020.png",
            "(*)" => "021.png",
            "(~)" => "022.png",
            "(8)" => "023.png",
            "(E)" => "024.png",
            "(F)" => "025.png",
            "(W)" => "026.png",
            "(O)" => "027.gif",
            "(K)" => "028.png",
            "(G)" => "029.png",
            "(^)" => "030.png",
            "(P)" => "031.png",
            "(I)" => "032.png",
            "(C)" => "033.png",
            "(T)" => "034.png",
            "({)" => "035.png",
            "(})" => "036.png",
            "(B)" => "037.png",
            "(D)" => "038.png",
            "(Z)" => "039.png",
            "(X)" => "040.png",
            "(Y)" => "041.png",
            "(N)" => "042.png",
            "(nnh)" => "044.png",
            "(#)" => "046.png",
            "(R)" => "047.png",
            "(sn)" => "054.png",
            "(tu)" => "055.png",
            "(pl)" => "056.png",
            "(||)" => "057.png",
            "(pi)" => "058.png",
            "(so)" => "059.png",
            "(au)" => "060.png",
            "(ap)" => "061.png",
            "(um)" => "062.png",
            "(ip)" => "063.png",
            "(co)" => "064.png",
            "(mp)" => "065.png",
            "(st)" => "067.png",
            "(pu)" => "102.png",
            "(yn)" => "068.png",
            "(h5)" => "069.gif",
            "(mo)" => "070.png",
            "(bah)" => "071.png",
            "(li)" => "074.gif",
            "(wo)" => "077.png",
            "'.'" => "080.png",
            "(bus)" => "045.png",
            "*p*" => "079.png",
            "*s*" => "085.png",
            "(M)" => "017.png",
            "(xx)" => "103.png"
        ];
        foreach($smiles as $smile => $img) {
            $i = str_replace('__smile__', $img, $this->Sources->route('assets', 'images'). '/smiles/__smile__');
            $html[] = str_replace(
                ["__url__", "__alt__"],
                [$i, $smile],
                '<img src="__url__" alt="__alt__" align="absmiddle" />'
            );
            $bbcode[] = $smile;
        }

		// REEMPLAZAMOS SMILEYS
        $this->text = str_replace($bbcode, $html, $this->text);
	}
}
