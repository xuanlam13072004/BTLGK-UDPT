<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/cloud/translate/v3/translation_service.proto

namespace Google\Cloud\Translate\V3;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The BatchTranslateDocument request.
 *
 * Generated from protobuf message <code>google.cloud.translation.v3.BatchTranslateDocumentRequest</code>
 */
class BatchTranslateDocumentRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Required. Location to make a regional call.
     * Format: `projects/{project-number-or-id}/locations/{location-id}`.
     * The `global` location is not supported for batch translation.
     * Only AutoML Translation models or glossaries within the same region (have
     * the same location-id) can be used, otherwise an INVALID_ARGUMENT (400)
     * error is returned.
     *
     * Generated from protobuf field <code>string parent = 1 [(.google.api.field_behavior) = REQUIRED, (.google.api.resource_reference) = {</code>
     */
    private $parent = '';
    /**
     * Required. The BCP-47 language code of the input document if known, for
     * example, "en-US" or "sr-Latn". Supported language codes are listed in
     * Language Support (https://cloud.google.com/translate/docs/languages).
     *
     * Generated from protobuf field <code>string source_language_code = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    private $source_language_code = '';
    /**
     * Required. The BCP-47 language code to use for translation of the input
     * document. Specify up to 10 language codes here.
     *
     * Generated from protobuf field <code>repeated string target_language_codes = 3 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    private $target_language_codes;
    /**
     * Required. Input configurations.
     * The total number of files matched should be <= 100.
     * The total content size to translate should be <= 100M Unicode codepoints.
     * The files must use UTF-8 encoding.
     *
     * Generated from protobuf field <code>repeated .google.cloud.translation.v3.BatchDocumentInputConfig input_configs = 4 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    private $input_configs;
    /**
     * Required. Output configuration.
     * If 2 input configs match to the same file (that is, same input path),
     * we don't generate output for duplicate inputs.
     *
     * Generated from protobuf field <code>.google.cloud.translation.v3.BatchDocumentOutputConfig output_config = 5 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    private $output_config = null;
    /**
     * Optional. The models to use for translation. Map's key is target language
     * code. Map's value is the model name. Value can be a built-in general model,
     * or an AutoML Translation model.
     * The value format depends on model type:
     * - AutoML Translation models:
     *   `projects/{project-number-or-id}/locations/{location-id}/models/{model-id}`
     * - General (built-in) models:
     *   `projects/{project-number-or-id}/locations/{location-id}/models/general/nmt`,
     * If the map is empty or a specific model is
     * not requested for a language pair, then default google model (nmt) is used.
     *
     * Generated from protobuf field <code>map<string, string> models = 6 [(.google.api.field_behavior) = OPTIONAL];</code>
     */
    private $models;
    /**
     * Optional. Glossaries to be applied. It's keyed by target language code.
     *
     * Generated from protobuf field <code>map<string, .google.cloud.translation.v3.TranslateTextGlossaryConfig> glossaries = 7 [(.google.api.field_behavior) = OPTIONAL];</code>
     */
    private $glossaries;
    /**
     * Optional. File format conversion map to be applied to all input files.
     * Map's key is the original mime_type. Map's value is the target mime_type of
     * translated documents.
     * Supported file format conversion includes:
     * - `application/pdf` to
     *   `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
     * If nothing specified, output files will be in the same format as the
     * original file.
     *
     * Generated from protobuf field <code>map<string, string> format_conversions = 8 [(.google.api.field_behavior) = OPTIONAL];</code>
     */
    private $format_conversions;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $parent
     *           Required. Location to make a regional call.
     *           Format: `projects/{project-number-or-id}/locations/{location-id}`.
     *           The `global` location is not supported for batch translation.
     *           Only AutoML Translation models or glossaries within the same region (have
     *           the same location-id) can be used, otherwise an INVALID_ARGUMENT (400)
     *           error is returned.
     *     @type string $source_language_code
     *           Required. The BCP-47 language code of the input document if known, for
     *           example, "en-US" or "sr-Latn". Supported language codes are listed in
     *           Language Support (https://cloud.google.com/translate/docs/languages).
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $target_language_codes
     *           Required. The BCP-47 language code to use for translation of the input
     *           document. Specify up to 10 language codes here.
     *     @type \Google\Cloud\Translate\V3\BatchDocumentInputConfig[]|\Google\Protobuf\Internal\RepeatedField $input_configs
     *           Required. Input configurations.
     *           The total number of files matched should be <= 100.
     *           The total content size to translate should be <= 100M Unicode codepoints.
     *           The files must use UTF-8 encoding.
     *     @type \Google\Cloud\Translate\V3\BatchDocumentOutputConfig $output_config
     *           Required. Output configuration.
     *           If 2 input configs match to the same file (that is, same input path),
     *           we don't generate output for duplicate inputs.
     *     @type array|\Google\Protobuf\Internal\MapField $models
     *           Optional. The models to use for translation. Map's key is target language
     *           code. Map's value is the model name. Value can be a built-in general model,
     *           or an AutoML Translation model.
     *           The value format depends on model type:
     *           - AutoML Translation models:
     *             `projects/{project-number-or-id}/locations/{location-id}/models/{model-id}`
     *           - General (built-in) models:
     *             `projects/{project-number-or-id}/locations/{location-id}/models/general/nmt`,
     *           If the map is empty or a specific model is
     *           not requested for a language pair, then default google model (nmt) is used.
     *     @type array|\Google\Protobuf\Internal\MapField $glossaries
     *           Optional. Glossaries to be applied. It's keyed by target language code.
     *     @type array|\Google\Protobuf\Internal\MapField $format_conversions
     *           Optional. File format conversion map to be applied to all input files.
     *           Map's key is the original mime_type. Map's value is the target mime_type of
     *           translated documents.
     *           Supported file format conversion includes:
     *           - `application/pdf` to
     *             `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
     *           If nothing specified, output files will be in the same format as the
     *           original file.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Cloud\Translate\V3\TranslationService::initOnce();
        parent::__construct($data);
    }

    /**
     * Required. Location to make a regional call.
     * Format: `projects/{project-number-or-id}/locations/{location-id}`.
     * The `global` location is not supported for batch translation.
     * Only AutoML Translation models or glossaries within the same region (have
     * the same location-id) can be used, otherwise an INVALID_ARGUMENT (400)
     * error is returned.
     *
     * Generated from protobuf field <code>string parent = 1 [(.google.api.field_behavior) = REQUIRED, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Required. Location to make a regional call.
     * Format: `projects/{project-number-or-id}/locations/{location-id}`.
     * The `global` location is not supported for batch translation.
     * Only AutoML Translation models or glossaries within the same region (have
     * the same location-id) can be used, otherwise an INVALID_ARGUMENT (400)
     * error is returned.
     *
     * Generated from protobuf field <code>string parent = 1 [(.google.api.field_behavior) = REQUIRED, (.google.api.resource_reference) = {</code>
     * @param string $var
     * @return $this
     */
    public function setParent($var)
    {
        GPBUtil::checkString($var, True);
        $this->parent = $var;

        return $this;
    }

    /**
     * Required. The BCP-47 language code of the input document if known, for
     * example, "en-US" or "sr-Latn". Supported language codes are listed in
     * Language Support (https://cloud.google.com/translate/docs/languages).
     *
     * Generated from protobuf field <code>string source_language_code = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return string
     */
    public function getSourceLanguageCode()
    {
        return $this->source_language_code;
    }

    /**
     * Required. The BCP-47 language code of the input document if known, for
     * example, "en-US" or "sr-Latn". Supported language codes are listed in
     * Language Support (https://cloud.google.com/translate/docs/languages).
     *
     * Generated from protobuf field <code>string source_language_code = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param string $var
     * @return $this
     */
    public function setSourceLanguageCode($var)
    {
        GPBUtil::checkString($var, True);
        $this->source_language_code = $var;

        return $this;
    }

    /**
     * Required. The BCP-47 language code to use for translation of the input
     * document. Specify up to 10 language codes here.
     *
     * Generated from protobuf field <code>repeated string target_language_codes = 3 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getTargetLanguageCodes()
    {
        return $this->target_language_codes;
    }

    /**
     * Required. The BCP-47 language code to use for translation of the input
     * document. Specify up to 10 language codes here.
     *
     * Generated from protobuf field <code>repeated string target_language_codes = 3 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setTargetLanguageCodes($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->target_language_codes = $arr;

        return $this;
    }

    /**
     * Required. Input configurations.
     * The total number of files matched should be <= 100.
     * The total content size to translate should be <= 100M Unicode codepoints.
     * The files must use UTF-8 encoding.
     *
     * Generated from protobuf field <code>repeated .google.cloud.translation.v3.BatchDocumentInputConfig input_configs = 4 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getInputConfigs()
    {
        return $this->input_configs;
    }

    /**
     * Required. Input configurations.
     * The total number of files matched should be <= 100.
     * The total content size to translate should be <= 100M Unicode codepoints.
     * The files must use UTF-8 encoding.
     *
     * Generated from protobuf field <code>repeated .google.cloud.translation.v3.BatchDocumentInputConfig input_configs = 4 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param \Google\Cloud\Translate\V3\BatchDocumentInputConfig[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setInputConfigs($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Cloud\Translate\V3\BatchDocumentInputConfig::class);
        $this->input_configs = $arr;

        return $this;
    }

    /**
     * Required. Output configuration.
     * If 2 input configs match to the same file (that is, same input path),
     * we don't generate output for duplicate inputs.
     *
     * Generated from protobuf field <code>.google.cloud.translation.v3.BatchDocumentOutputConfig output_config = 5 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Cloud\Translate\V3\BatchDocumentOutputConfig|null
     */
    public function getOutputConfig()
    {
        return isset($this->output_config) ? $this->output_config : null;
    }

    public function hasOutputConfig()
    {
        return isset($this->output_config);
    }

    public function clearOutputConfig()
    {
        unset($this->output_config);
    }

    /**
     * Required. Output configuration.
     * If 2 input configs match to the same file (that is, same input path),
     * we don't generate output for duplicate inputs.
     *
     * Generated from protobuf field <code>.google.cloud.translation.v3.BatchDocumentOutputConfig output_config = 5 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param \Google\Cloud\Translate\V3\BatchDocumentOutputConfig $var
     * @return $this
     */
    public function setOutputConfig($var)
    {
        GPBUtil::checkMessage($var, \Google\Cloud\Translate\V3\BatchDocumentOutputConfig::class);
        $this->output_config = $var;

        return $this;
    }

    /**
     * Optional. The models to use for translation. Map's key is target language
     * code. Map's value is the model name. Value can be a built-in general model,
     * or an AutoML Translation model.
     * The value format depends on model type:
     * - AutoML Translation models:
     *   `projects/{project-number-or-id}/locations/{location-id}/models/{model-id}`
     * - General (built-in) models:
     *   `projects/{project-number-or-id}/locations/{location-id}/models/general/nmt`,
     * If the map is empty or a specific model is
     * not requested for a language pair, then default google model (nmt) is used.
     *
     * Generated from protobuf field <code>map<string, string> models = 6 [(.google.api.field_behavior) = OPTIONAL];</code>
     * @return \Google\Protobuf\Internal\MapField
     */
    public function getModels()
    {
        return $this->models;
    }

    /**
     * Optional. The models to use for translation. Map's key is target language
     * code. Map's value is the model name. Value can be a built-in general model,
     * or an AutoML Translation model.
     * The value format depends on model type:
     * - AutoML Translation models:
     *   `projects/{project-number-or-id}/locations/{location-id}/models/{model-id}`
     * - General (built-in) models:
     *   `projects/{project-number-or-id}/locations/{location-id}/models/general/nmt`,
     * If the map is empty or a specific model is
     * not requested for a language pair, then default google model (nmt) is used.
     *
     * Generated from protobuf field <code>map<string, string> models = 6 [(.google.api.field_behavior) = OPTIONAL];</code>
     * @param array|\Google\Protobuf\Internal\MapField $var
     * @return $this
     */
    public function setModels($var)
    {
        $arr = GPBUtil::checkMapField($var, \Google\Protobuf\Internal\GPBType::STRING, \Google\Protobuf\Internal\GPBType::STRING);
        $this->models = $arr;

        return $this;
    }

    /**
     * Optional. Glossaries to be applied. It's keyed by target language code.
     *
     * Generated from protobuf field <code>map<string, .google.cloud.translation.v3.TranslateTextGlossaryConfig> glossaries = 7 [(.google.api.field_behavior) = OPTIONAL];</code>
     * @return \Google\Protobuf\Internal\MapField
     */
    public function getGlossaries()
    {
        return $this->glossaries;
    }

    /**
     * Optional. Glossaries to be applied. It's keyed by target language code.
     *
     * Generated from protobuf field <code>map<string, .google.cloud.translation.v3.TranslateTextGlossaryConfig> glossaries = 7 [(.google.api.field_behavior) = OPTIONAL];</code>
     * @param array|\Google\Protobuf\Internal\MapField $var
     * @return $this
     */
    public function setGlossaries($var)
    {
        $arr = GPBUtil::checkMapField($var, \Google\Protobuf\Internal\GPBType::STRING, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Cloud\Translate\V3\TranslateTextGlossaryConfig::class);
        $this->glossaries = $arr;

        return $this;
    }

    /**
     * Optional. File format conversion map to be applied to all input files.
     * Map's key is the original mime_type. Map's value is the target mime_type of
     * translated documents.
     * Supported file format conversion includes:
     * - `application/pdf` to
     *   `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
     * If nothing specified, output files will be in the same format as the
     * original file.
     *
     * Generated from protobuf field <code>map<string, string> format_conversions = 8 [(.google.api.field_behavior) = OPTIONAL];</code>
     * @return \Google\Protobuf\Internal\MapField
     */
    public function getFormatConversions()
    {
        return $this->format_conversions;
    }

    /**
     * Optional. File format conversion map to be applied to all input files.
     * Map's key is the original mime_type. Map's value is the target mime_type of
     * translated documents.
     * Supported file format conversion includes:
     * - `application/pdf` to
     *   `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
     * If nothing specified, output files will be in the same format as the
     * original file.
     *
     * Generated from protobuf field <code>map<string, string> format_conversions = 8 [(.google.api.field_behavior) = OPTIONAL];</code>
     * @param array|\Google\Protobuf\Internal\MapField $var
     * @return $this
     */
    public function setFormatConversions($var)
    {
        $arr = GPBUtil::checkMapField($var, \Google\Protobuf\Internal\GPBType::STRING, \Google\Protobuf\Internal\GPBType::STRING);
        $this->format_conversions = $arr;

        return $this;
    }

}

