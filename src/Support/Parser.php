<?php namespace Anomaly\Streams\Platform\Support;

use Illuminate\Contracts\Support\Arrayable;
use StringTemplate\Engine;

/**
 * Class Parser
 *
 * @link          http://anomaly.is/streams-platform
 * @author        AnomalyLabs, Inc. <hello@anomaly.is>
 * @author        Ryan Thompson <ryan@anomaly.is>
 * @package       Anomaly\Streams\Platform\Support
 */
class Parser
{

    /**
     * The URL generator.
     *
     * @var Url
     */
    protected $url;

    /**
     * The string parser.
     *
     * @var Engine
     */
    protected $parser;

    /**
     * The request object.
     *
     * @var Request
     */
    protected $request;

    /**
     * Create a new Parser instance.
     *
     * @param Url     $url
     * @param Engine  $parser
     * @param Request $request
     */
    public function __construct(Url $url, Engine $parser, Request $request)
    {
        $this->url     = $url;
        $this->parser  = $parser;
        $this->request = $request;
    }

    /**
     * Parse data into the target recursively.
     *
     * @param       $target
     * @param array $data
     * @return mixed
     */
    public function parse($target, array $data = [])
    {
        $data = $this->mergeDefaultData($data);

        /**
         * If the target is an array
         * then parse it recursively.
         */
        if (is_array($target)) {
            foreach ($target as $key => &$value) {
                $value = $this->parse($value, $data);
            }
        }

        /**
         * if the target is a string and is in a parsable
         * format then parse the target with the payload.
         */
        if (is_string($target) && str_contains($target, ['{', '}'])) {
            $target = $this->parser->render($target, $this->prepData($data));
        }

        return $target;
    }

    /**
     * Merge default data.
     *
     * @param array $data
     * @return array
     */
    protected function mergeDefaultData(array $data)
    {
        $url     = $this->url->toArray();
        $request = $this->request->toArray();

        return array_merge(compact('url', 'request'), $data);
    }

    /**
     * Return the target cleaned of remaining tags.
     *
     * @param $target
     * @return mixed
     */
    protected function clean($target)
    {

        /**
         * If the target is an array
         * then clean it recursively.
         */
        if (is_array($target)) {
            foreach ($target as &$value) {
                $value = $this->clean($value);
            }
        }

        /**
         * if the target is a string then
         * clean it as is.
         */
        if (is_string($target) && str_contains($target, ['{', '}'])) {
            $target = preg_replace("/\{[a-z._]+?\}/", '', $target);
        }

        return $target;
    }

    /**
     * Prep data for parsing.
     *
     * @param array $data
     * @return array
     */
    protected function prepData(array $data)
    {
        foreach ($data as $key => &$value) {
            if (is_object($value) && $value instanceof Arrayable) {
                $value = $value->toArray();
            }
        }

        return $data;
    }
}
