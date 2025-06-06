<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/cloud/translate/v3/translation_service.proto

namespace Google\Cloud\Translate\V3;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * State metadata for the batch translation operation.
 *
 * Generated from protobuf message <code>google.cloud.translation.v3.BatchTranslateDocumentMetadata</code>
 */
class BatchTranslateDocumentMetadata extends \Google\Protobuf\Internal\Message
{
    /**
     * The state of the operation.
     *
     * Generated from protobuf field <code>.google.cloud.translation.v3.BatchTranslateDocumentMetadata.State state = 1;</code>
     */
    private $state = 0;
    /**
     * Total number of pages to translate in all documents so far. Documents
     * without clear page definition (such as XLSX) are not counted.
     *
     * Generated from protobuf field <code>int64 total_pages = 2;</code>
     */
    private $total_pages = 0;
    /**
     * Number of successfully translated pages in all documents so far. Documents
     * without clear page definition (such as XLSX) are not counted.
     *
     * Generated from protobuf field <code>int64 translated_pages = 3;</code>
     */
    private $translated_pages = 0;
    /**
     * Number of pages that failed to process in all documents so far. Documents
     * without clear page definition (such as XLSX) are not counted.
     *
     * Generated from protobuf field <code>int64 failed_pages = 4;</code>
     */
    private $failed_pages = 0;
    /**
     * Number of billable pages in documents with clear page definition (such as
     * PDF, DOCX, PPTX) so far.
     *
     * Generated from protobuf field <code>int64 total_billable_pages = 5;</code>
     */
    private $total_billable_pages = 0;
    /**
     * Total number of characters (Unicode codepoints) in all documents so far.
     *
     * Generated from protobuf field <code>int64 total_characters = 6;</code>
     */
    private $total_characters = 0;
    /**
     * Number of successfully translated characters (Unicode codepoints) in all
     * documents so far.
     *
     * Generated from protobuf field <code>int64 translated_characters = 7;</code>
     */
    private $translated_characters = 0;
    /**
     * Number of characters that have failed to process (Unicode codepoints) in
     * all documents so far.
     *
     * Generated from protobuf field <code>int64 failed_characters = 8;</code>
     */
    private $failed_characters = 0;
    /**
     * Number of billable characters (Unicode codepoints) in documents without
     * clear page definition (such as XLSX) so far.
     *
     * Generated from protobuf field <code>int64 total_billable_characters = 9;</code>
     */
    private $total_billable_characters = 0;
    /**
     * Time when the operation was submitted.
     *
     * Generated from protobuf field <code>.google.protobuf.Timestamp submit_time = 10;</code>
     */
    private $submit_time = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $state
     *           The state of the operation.
     *     @type int|string $total_pages
     *           Total number of pages to translate in all documents so far. Documents
     *           without clear page definition (such as XLSX) are not counted.
     *     @type int|string $translated_pages
     *           Number of successfully translated pages in all documents so far. Documents
     *           without clear page definition (such as XLSX) are not counted.
     *     @type int|string $failed_pages
     *           Number of pages that failed to process in all documents so far. Documents
     *           without clear page definition (such as XLSX) are not counted.
     *     @type int|string $total_billable_pages
     *           Number of billable pages in documents with clear page definition (such as
     *           PDF, DOCX, PPTX) so far.
     *     @type int|string $total_characters
     *           Total number of characters (Unicode codepoints) in all documents so far.
     *     @type int|string $translated_characters
     *           Number of successfully translated characters (Unicode codepoints) in all
     *           documents so far.
     *     @type int|string $failed_characters
     *           Number of characters that have failed to process (Unicode codepoints) in
     *           all documents so far.
     *     @type int|string $total_billable_characters
     *           Number of billable characters (Unicode codepoints) in documents without
     *           clear page definition (such as XLSX) so far.
     *     @type \Google\Protobuf\Timestamp $submit_time
     *           Time when the operation was submitted.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Cloud\Translate\V3\TranslationService::initOnce();
        parent::__construct($data);
    }

    /**
     * The state of the operation.
     *
     * Generated from protobuf field <code>.google.cloud.translation.v3.BatchTranslateDocumentMetadata.State state = 1;</code>
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * The state of the operation.
     *
     * Generated from protobuf field <code>.google.cloud.translation.v3.BatchTranslateDocumentMetadata.State state = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setState($var)
    {
        GPBUtil::checkEnum($var, \Google\Cloud\Translate\V3\BatchTranslateDocumentMetadata\State::class);
        $this->state = $var;

        return $this;
    }

    /**
     * Total number of pages to translate in all documents so far. Documents
     * without clear page definition (such as XLSX) are not counted.
     *
     * Generated from protobuf field <code>int64 total_pages = 2;</code>
     * @return int|string
     */
    public function getTotalPages()
    {
        return $this->total_pages;
    }

    /**
     * Total number of pages to translate in all documents so far. Documents
     * without clear page definition (such as XLSX) are not counted.
     *
     * Generated from protobuf field <code>int64 total_pages = 2;</code>
     * @param int|string $var
     * @return $this
     */
    public function setTotalPages($var)
    {
        GPBUtil::checkInt64($var);
        $this->total_pages = $var;

        return $this;
    }

    /**
     * Number of successfully translated pages in all documents so far. Documents
     * without clear page definition (such as XLSX) are not counted.
     *
     * Generated from protobuf field <code>int64 translated_pages = 3;</code>
     * @return int|string
     */
    public function getTranslatedPages()
    {
        return $this->translated_pages;
    }

    /**
     * Number of successfully translated pages in all documents so far. Documents
     * without clear page definition (such as XLSX) are not counted.
     *
     * Generated from protobuf field <code>int64 translated_pages = 3;</code>
     * @param int|string $var
     * @return $this
     */
    public function setTranslatedPages($var)
    {
        GPBUtil::checkInt64($var);
        $this->translated_pages = $var;

        return $this;
    }

    /**
     * Number of pages that failed to process in all documents so far. Documents
     * without clear page definition (such as XLSX) are not counted.
     *
     * Generated from protobuf field <code>int64 failed_pages = 4;</code>
     * @return int|string
     */
    public function getFailedPages()
    {
        return $this->failed_pages;
    }

    /**
     * Number of pages that failed to process in all documents so far. Documents
     * without clear page definition (such as XLSX) are not counted.
     *
     * Generated from protobuf field <code>int64 failed_pages = 4;</code>
     * @param int|string $var
     * @return $this
     */
    public function setFailedPages($var)
    {
        GPBUtil::checkInt64($var);
        $this->failed_pages = $var;

        return $this;
    }

    /**
     * Number of billable pages in documents with clear page definition (such as
     * PDF, DOCX, PPTX) so far.
     *
     * Generated from protobuf field <code>int64 total_billable_pages = 5;</code>
     * @return int|string
     */
    public function getTotalBillablePages()
    {
        return $this->total_billable_pages;
    }

    /**
     * Number of billable pages in documents with clear page definition (such as
     * PDF, DOCX, PPTX) so far.
     *
     * Generated from protobuf field <code>int64 total_billable_pages = 5;</code>
     * @param int|string $var
     * @return $this
     */
    public function setTotalBillablePages($var)
    {
        GPBUtil::checkInt64($var);
        $this->total_billable_pages = $var;

        return $this;
    }

    /**
     * Total number of characters (Unicode codepoints) in all documents so far.
     *
     * Generated from protobuf field <code>int64 total_characters = 6;</code>
     * @return int|string
     */
    public function getTotalCharacters()
    {
        return $this->total_characters;
    }

    /**
     * Total number of characters (Unicode codepoints) in all documents so far.
     *
     * Generated from protobuf field <code>int64 total_characters = 6;</code>
     * @param int|string $var
     * @return $this
     */
    public function setTotalCharacters($var)
    {
        GPBUtil::checkInt64($var);
        $this->total_characters = $var;

        return $this;
    }

    /**
     * Number of successfully translated characters (Unicode codepoints) in all
     * documents so far.
     *
     * Generated from protobuf field <code>int64 translated_characters = 7;</code>
     * @return int|string
     */
    public function getTranslatedCharacters()
    {
        return $this->translated_characters;
    }

    /**
     * Number of successfully translated characters (Unicode codepoints) in all
     * documents so far.
     *
     * Generated from protobuf field <code>int64 translated_characters = 7;</code>
     * @param int|string $var
     * @return $this
     */
    public function setTranslatedCharacters($var)
    {
        GPBUtil::checkInt64($var);
        $this->translated_characters = $var;

        return $this;
    }

    /**
     * Number of characters that have failed to process (Unicode codepoints) in
     * all documents so far.
     *
     * Generated from protobuf field <code>int64 failed_characters = 8;</code>
     * @return int|string
     */
    public function getFailedCharacters()
    {
        return $this->failed_characters;
    }

    /**
     * Number of characters that have failed to process (Unicode codepoints) in
     * all documents so far.
     *
     * Generated from protobuf field <code>int64 failed_characters = 8;</code>
     * @param int|string $var
     * @return $this
     */
    public function setFailedCharacters($var)
    {
        GPBUtil::checkInt64($var);
        $this->failed_characters = $var;

        return $this;
    }

    /**
     * Number of billable characters (Unicode codepoints) in documents without
     * clear page definition (such as XLSX) so far.
     *
     * Generated from protobuf field <code>int64 total_billable_characters = 9;</code>
     * @return int|string
     */
    public function getTotalBillableCharacters()
    {
        return $this->total_billable_characters;
    }

    /**
     * Number of billable characters (Unicode codepoints) in documents without
     * clear page definition (such as XLSX) so far.
     *
     * Generated from protobuf field <code>int64 total_billable_characters = 9;</code>
     * @param int|string $var
     * @return $this
     */
    public function setTotalBillableCharacters($var)
    {
        GPBUtil::checkInt64($var);
        $this->total_billable_characters = $var;

        return $this;
    }

    /**
     * Time when the operation was submitted.
     *
     * Generated from protobuf field <code>.google.protobuf.Timestamp submit_time = 10;</code>
     * @return \Google\Protobuf\Timestamp|null
     */
    public function getSubmitTime()
    {
        return isset($this->submit_time) ? $this->submit_time : null;
    }

    public function hasSubmitTime()
    {
        return isset($this->submit_time);
    }

    public function clearSubmitTime()
    {
        unset($this->submit_time);
    }

    /**
     * Time when the operation was submitted.
     *
     * Generated from protobuf field <code>.google.protobuf.Timestamp submit_time = 10;</code>
     * @param \Google\Protobuf\Timestamp $var
     * @return $this
     */
    public function setSubmitTime($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Timestamp::class);
        $this->submit_time = $var;

        return $this;
    }

}

