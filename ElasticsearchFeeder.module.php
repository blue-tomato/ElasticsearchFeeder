<?php

class ElasticsearchFeeder extends WireData implements Module, ConfigurableModule
{

	private $elasticSearchMetaKeyName = 'elasticsearchfeeder_meta';

	/**
	 * Description of the module including meta data
	 *
	 */
	public static function getModuleInfo()
	{
		return array(
			'title' => 'ElasticsearchFeeder',
			'class' => 'ElasticsearchFeeder',
			'version' => 130,
			'summary' => 'Schema-flexible module for getting your page into ElasticSearch',
			'href' => 'https://github.com/blue-tomato/ElasticsearchFeeder/',
			'singular' => true,
			'autoload' => true,
			'requires' => [
				'PHP>=7.0.0',
				'ProcessWire>=3.0.133'
			]
		);
	}

	/**
	 * Register all needed hooks with proper method references
	 *
	 * You can see where those hooks hook in checking the ProcessWire docs
	 *
	 */
	public function init()
	{

		// don't add hooks if elasticsearchFeederDisabled is active i.e. in dev mode
		$config = $this->wire('config');
		if ($config->elasticsearchFeederDisabled === true) {
			return false;
		}

		$this->pages->addHookAfter('save', $this, 'afterPageSave');
		$this->pages->addHookBefore('delete', $this, 'beforePageDelete');
		$this->pages->addHookBefore('trash', $this, 'beforePageTrash');
		$this->pages->addHookAfter('restored', $this, 'afterPageRestored');
		$this->pages->addHookAfter('unpublished', $this, 'afterPageUnpublished');
		$this->pages->addHookAfter('published', $this, 'afterPagePublished');

		// event = admin/module-config page render + check es_update paramter
		// 'Page::render' here means "Admin-Page Render" (not client page render)
		$this->addHookAfter('Page::render', $this, 'reIndexButtonClick');
	}


	public static function getModuleConfigInputfields(array $data)
	{
		$wrapper = new InputfieldWrapper();
		$modules = wire('modules');
		$templates = wire('templates');
		$config = wire('config');

		// show warning if ElasticSearchFeeder is deactivated
		// i.e. for development or stage environments
		if ($config->elasticsearchFeederDisabled) {
			wire()->warning("Warning: ElasticsearchFeeder is deactivated throw config.php");
		} elseif ($data['es_debug_mode'] && $data['es_debug_mode'] === 'on') {
			wire()->warning("Warning: ElasticSearch Debug Mode is enabled");
		} elseif ($config->elasticsearchFeederConnectionOverride) {
			wire()->warning("Warning: ElasticsearchFeeder Server Connection are overwritten by config.php");
		}

		$field = $modules->get('InputfieldSelect');
		$field->name = "es_debug_mode";
		$field->required = true;
		$field->label = 'ElasticSearch Debug Mode';
		$field->description = __("If debug mode is enabled, instead of sending the page to ES, the schema will be output to the message-bar on page save.");
		$field->options = array(
			'off' => 'off',
			'on' => 'on'
		);
		$field->value = isset($data['es_debug_mode']) ? $data['es_debug_mode'] : 'off';
		$wrapper->add($field);

		$field = $modules->get('InputfieldSelect');
		$field->name = "es_protocol";
		$field->required = true;
		$field->label = 'ElasticSearch Endpoint Protocol';
		$field->options = array(
			'https' => 'https',
			'http' => 'http'
		);
		$field->value = isset($data['es_protocol']) ? $data['es_protocol'] : 'https';
		$wrapper->add($field);

		$field = $modules->get("InputfieldText");
		$field->name = "es_host";
		$field->required = true;
		$field->label = __("ElasticSearch Host");
		$field->value = isset($data['es_host']) ? $data['es_host'] : '';
		$field->description = __("An IP address will do, i.e. '127.0.0.1'.");
		$field->placeholder = "i.e. 127.0.0.1";
		$wrapper->add($field);

		$field = $modules->get("InputfieldText");
		$field->name = "es_access_key";
		$field->label = __("ElasticSearch Access Key");
		$field->value = isset($data['es_access_key']) ? $data['es_access_key'] : '';
		$field->description = __("Your ElasticSearch Access Key / Bonsai");
		$wrapper->add($field);

		$field = $modules->get("InputfieldText");
		$field->name = "es_access_secret";
		$field->label = __("ElasticSearch Access Secret");
		$field->value = isset($data['es_access_secret']) ? $data['es_access_secret'] : '';
		$field->description = __("Your ElasticSearch Access Secret / Bonsai");
		$wrapper->add($field);

		$field = $modules->get("InputfieldText");
		$field->name = "es_schema_path";
		$field->label = __("ElasticSearch Schema Path");
		$field->value = isset($data['es_schema_path']) ? $data['es_schema_path'] : '';
		$field->description = __("Path where you Schema / Template Mapping are saved. Has to be in /site/templates/");
		$field->placeholder = "i.e. foo/bar/elastichSearchSchema";
		$wrapper->add($field);

		$field = $modules->get("InputfieldText");
		$field->name = "es_index_id_prefix";
		$field->label = __("Prefix for your ES document _id's");
		$field->value = isset($data['es_index_id_prefix']) ? $data['es_index_id_prefix'] : 'processwire';
		$field->placeholder = "i.e. processwire";
		$field->required = true;
		$wrapper->add($field);

		foreach ($templates as $template) {

			// WARNING: don't allow system templates like admin, roles or permissions...
			if ($template->flags === Template::flagSystem) continue;

			$field = $modules->get('InputfieldMarkup');
			$field->label  = __("Configuration for template: $template->name");

			$templateField = $modules->get('InputfieldSelect');
			$templateField->name = "es_template_index_$template->id";
			$templateField->required = true;
			$templateField->label = 'Index this template?';
			$templateField->options = array(
				'yes' => 'yes',
				'no' => 'no'
			);
			$templateField->value = isset($data["es_template_index_$template->id"]) ? $data["es_template_index_$template->id"] : 'no';
			$field->add($templateField);

			$templateField = $modules->get("InputfieldText");
			$templateField->name = "es_template_indexname_$template->id";
			if (isset($data["es_template_index_$template->id"]) && $data["es_template_index_$template->id"] == 'yes') {
				$templateField->required = true;
			}
			$templateField->label = __("Document Index Name");
			$templateField->value = isset($data["es_template_indexname_$template->id"]) ? $data["es_template_indexname_$template->id"] : $template->name;
			$templateField->placeholder = "i.e. $template->name";
			$field->add($templateField);

			$templateField = $modules->get("InputfieldText");
			$templateField->name = "es_template_type_$template->id";
			if (isset($data["es_template_index_$template->id"]) && $data["es_template_index_$template->id"] === 'yes') {
				$templateField->required = true;
			}
			$templateField->label = __("Document Type Name");
			$templateField->value = isset($data["es_template_type_$template->id"]) ? $data["es_template_type_$template->id"] : $template->name;
			$templateField->placeholder = "i.e. $template->name";
			$field->add($templateField);

			$wrapper->add($field);
		}

		$field = $modules->get('InputfieldMarkup');
		$field->label  = __('Update ES index');

		$field_button = $modules->get('InputfieldButton');
		$field_button->name = 'update_all_pages';
		$field_button->value = __('Index All Pages');
		$field_button->href = 'edit?name=' . wire('input')->get('name') . '&es_update=all_pages';
		$field_button->description = __("Indexes all ES-relevant pages. WARNING: Can run very long if you have a lot of pages.");
		$field->add($field_button);

		foreach ($templates as $template) {

			// WARNING: don't allow system templates like admin, roles or permissions...
			if ($template->flags === Template::flagSystem) continue;

			// render only button for allowed templates
			if (!isset($data["es_template_index_$template->id"]) || (isset($data["es_template_index_$template->id"]) && $data["es_template_index_$template->id"] === 'no')) continue;

			$field_button = $modules->get('InputfieldButton');
			$field_button->name = 'update_all_pages_' . $template->id;
			$field_button->value = "Index All {$template->name} Pages";
			$field_button->href = 'edit?name=' . wire('input')->get('name') . '&es_update=all_pages&template_id=' . $template->id;

			$field->add($field_button);
		}

		$wrapper->add($field);

		return $wrapper;
	}

	public function reIndexButtonClick(HookEvent $event)
	{

		// check whether reIndex button was clicked
		// to to say an eventListener checking the proper url parameter in case of a admin-page reload
		if ($this->input->get('es_update') != 'all_pages' || $event->object->template != 'admin') return;

		// prevent server timeouts
		// works only if php safe_mode is off
		set_time_limit(0);

		$templateId = false;
		if ($this->input->get('template_id')) $templateId = $this->input->get('template_id');

		// all es-relavant pages will be updated
		$msg = $this->indexAllAllowedPages($templateId);

		// log update process return/message
		$this->session->message($msg);
	}

	public function sendDocumentToElasticSearch(Page $page)
	{
		//check whether template is allowed for indexation (set on config page)
		if (!$this->checkAllowedTemplate($page->template)) return;

		$config = $this->wire('config');

		$typeName = $this->getElasticSearchDocumentType($page->template);
		$id = $this->createElasticSearchDocumentHashedId($page->id, $this->getIndexPrefix());
		$url = $this->getElasticSearchDocumentIndexUrl($page->template, $typeName, $id);

		// $this->dashesToCamelCase() i.e. converts content-page to contentPage
		$documentSchemaFunctionName = $this->dashesToCamelCase($page->template->name);

		$documentTypeSchemaPath = "{$config->paths->templates}/{$this->getElasticSearchSchemaPath()}/{$page->template->name}.schema.php";

		if (file_exists($documentTypeSchemaPath)) {
			include_once($documentTypeSchemaPath);
			// from now on $documentSchemaFunctionName() from the schema file should be available as a function
			$indexPrefix = $this->getIndexPrefix();
			$document = $documentSchemaFunctionName($page);
			$document["page-id"] = $page->id;
			$document["prefix"] = $this->getIndexPrefix();
		} else {
			$this->log("Error: no valid schema defined for template: {$page->template->name} ({$typeName})");
		}


		if ($this->isDebugModeEnabled()) {
			$this->session->message('ElasticSearch Debug Output: <hr/> <pre>' . json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</pre><p>Note: this Elasticsearch debugging info can be deactivated on its <a href="../../module/edit?name=ElasticsearchFeeder#wrap_Inputfield_es_debug_mode">module page configuration</a>.</p>', Notice::allowMarkup);

			return false;
		}

		if ($document) {
			$res = $this->curlJsonGet($url, $document);

			if (isset($res['status']) && $res['status'] === 404) {
				//not indexed successfully, log error message from elasticsearch
				$this->log("Error for page with id {$page->id}: no valid Elasticsearch indexation: Response Status: {$res->status}; Error: {$res->error->type}");
				return false;
			} else if (isset($res['error'])) {
				$msgString = json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				$this->log("Error for page with id {$page->id}: {$msgString}");
				return false;
			} else {
				//indexed successfully
				return $id;
			}
		}

		return false;
	}

	public function afterPageSave(HookEvent $event)
	{
		$page = $event->arguments[0];

		// check if the page isn't in the trash and is published and viewable for all
		if (!$page->isTrash() && $page->isPublic() && $this->checkAllowedTemplate($page->template)) {

			$success = $this->sendDocumentToElasticSearch($page);

			if ($success) {
				$page->meta($this->elasticSearchMetaKeyName, [
					"es_id" => $success,
					"lastindex" => date('c')
				]);
				$this->session->message("Document successful sent to ElasticSearch.");
			} else {
				$this->session->warning("Can't sent document (pageId: {$page->id}) to ElasticSearch.", Notice::log);
			}
		}
	}

	public function afterPagePublished(HookEvent $event)
	{
		$page = $event->arguments[0];

		// check if the page isn't in the trash and is published and viewable for all
		if (!$page->isTrash() && $page->isPublic() && $this->checkAllowedTemplate($page->template)) {
			$success = $this->sendDocumentToElasticSearch($page);

			if ($success) {
				$this->session->message("Document successful sent to ElasticSearch.");
			} else {
				$this->session->warning("Can't sent document (pageId: {$page->id}) to ElasticSearch.", Notice::log);
			}
		}
	}

	public function afterPageUnpublished(HookEvent $event)
	{
		$page = $event->arguments[0];

		if ($this->checkAllowedTemplate($page->template)) {
			$res = $this->curlJsonDelete($page);
			if ($res) { //TODO parse and check reponse
				$this->session->message("Document removed from ElasticSearch.");
			} else {
				$this->session->warning("Can't remove document (pageId: {$page->id}) from ElasticSearch", Notice::log);
			}
		}
	}

	public function dashesToCamelCase(string $string, boolean $capitalizeFirstCharacter = null)
	{
		$str = str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
		if (!$capitalizeFirstCharacter) {
			$str[0] = strtolower($str[0]);
		}
		return $str;
	}

	public function afterPageRestored(HookEvent $event)
	{
		$page = $event->arguments[0];

		if (!$page->isTrash() && $page->isPublic() && $this->checkAllowedTemplate($page->template)) {
			$success = $this->sendDocumentToElasticSearch($page);

			if ($success) {
				$this->session->message("Document successful sent to ElasticSearch.");
			} else {
				$this->session->warning("Can't sent document (pageId: {$page->id}) to ElasticSearch.", Notice::log);
			}
		}
	}

	public function beforePageDelete(HookEvent $event)
	{

		$page = $event->arguments[0];
		if ($this->checkAllowedTemplate($page->template)) {
			$res = $this->curlJsonDelete($page);

			if ($res) { //TODO parse and check reponse
				$this->session->message("Document removed from ElasticSearch.");
			} else {
				$this->session->warning("Can't remove document (pageId: {$page->id}) from ElasticSearch", Notice::log);
			}
		}
	}

	public function beforePageTrash(HookEvent $event)
	{

		$page = $event->arguments[0];
		if ($this->checkAllowedTemplate($page->template)) {
			$res = $this->curlJsonDelete($page);

			if ($res) { //TODO parse and check reponse
				$this->session->message("Document removed from ElasticSearch.");
			} else {
				$this->session->warning("Can't remove document (pageId: {$page->id}) from ElasticSearch", Notice::log);
			}
		}
	}

	public function curlJsonGet(string $url, $data)
	{

		return $this->curlJsonRequest(null, $url, $data);
	}

	public function curlJsonDeleteByElasticSearchId(string $esDocumentId, string $type, string $index)
	{
		$url = sprintf(
			'%s/%s/%s/%s',
			$this->getElasticSearchUrlBase(),
			$index,
			$type,
			$esDocumentId
		);

		return $this->curlJsonRequest('DELETE', $url);
	}

	protected function curlJsonDelete(Page $page)
	{
		$esDocumentId = $this->createElasticSearchDocumentHashedId($page->id, $this->getIndexPrefix());
		$type = $this->getElasticSearchDocumentType($page->template);
		$index = $this->getElasticSearchIndexName($page->template);
		$url = sprintf(
			'%s/%s/%s/%s',
			$this->getElasticSearchUrlBase(),
			$index,
			$type,
			$esDocumentId
		);

		return $this->curlJsonRequest('DELETE', $url);
	}

	public function curlJsonRequest($method = null, string $url = '', $data = null)
	{
		$config = $this->wire('config');

		$ch = curl_init();

		$curlConfig = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
			)
		);

		if ($config->httpsProxy && $this->getElasticSearchProtocol() === 'https') {
			$curlConfig[CURLOPT_PROXY] = $config->httpsProxy;
		} else if ($config->httpProxy && $this->getElasticSearchProtocol() === 'http') {
			$curlConfig[CURLOPT_PROXY] = $config->httpProxy;
		}

		if (!is_null($method))
			$curlConfig[CURLOPT_CUSTOMREQUEST] = $method;

		if (!is_null($data))
			$curlConfig[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		curl_setopt_array($ch, $curlConfig);

		$response = curl_exec($ch);

		if ($response === false) {

			$this->session->error('ElasticSearch: ' . curl_error($ch));
			return null;
		}

		return json_decode($response, true);
	}

	public function getElasticSearchUrlBase()
	{

		$credentials = "";
		if (!empty($this->getElasticSearchAccessKey()) && !empty($this->getElasticSearchAccessSecret())) {
			$credentials = sprintf(
				'%s:%s@',
				$this->getElasticSearchAccessKey(),
				$this->getElasticSearchAccessSecret()
			);
		}

		return sprintf(
			'%s://%s%s',
			$this->getElasticSearchProtocol(),
			$credentials,
			$this->getElasticSearchHost()
		);
	}

	public function getElasticSearchDocumentType(Template $template)
	{
		return $this->get("es_template_type_$template->id");
	}

	public function checkAllowedTemplate(Template $template)
	{

		// WARNING: don't allow system templates like admin, roles or permissions...
		if ($template->flags === Template::flagSystem) return false;

		$value = $this->get("es_template_index_$template->id");
		return (isset($value) && $value === 'yes') ? true : false;
	}

	public function createElasticSearchDocumentHashedId(string $id, string $prefix = '')
	{
		return sha1($prefix . $id);
	}

	protected function getElasticSearchDocumentIndexUrl(Template $template, string $type, string $esId)
	{
		return sprintf(
			'%s/%s/%s/%s',
			$this->getElasticSearchUrlBase(),
			$this->getElasticSearchIndexName($template),
			$type,
			$esId
		);
	}

	protected function getSiteIndex()
	{

		return preg_replace('/[^a-z]/', '_', strtolower($this->config->httpHost));
	}

	protected function getElasticSearchHost()
	{

		$config = $this->wire('config');
		if (isset($config->elasticsearchFeederConnectionOverride['es_host'])) {
			return $config->elasticsearchFeederConnectionOverride['es_host'];
		}

		return $this->get('es_host');
	}

	protected function getElasticSearchSchemaPath()
	{
		return $this->get('es_schema_path');
	}

	public function getIndexPrefix()
	{
		$prefix = $this->get('es_index_id_prefix');
		return (isset($prefix) && !empty($prefix)) ? $prefix : '';
	}

	protected function getElasticSearchAccessKey()
	{

		$config = $this->wire('config');
		if (isset($config->elasticsearchFeederConnectionOverride['es_access_key'])) {
			return $config->elasticsearchFeederConnectionOverride['es_access_key'];
		}

		return $this->get('es_access_key');
	}

	protected function getElasticSearchAccessSecret()
	{

		$config = $this->wire('config');
		if (isset($config->elasticsearchFeederConnectionOverride['es_access_secret'])) {
			return $config->elasticsearchFeederConnectionOverride['es_access_secret'];
		}

		return $this->get('es_access_secret');
	}

	protected function getElasticSearchProtocol()
	{

		$config = $this->wire('config');
		if (isset($config->elasticsearchFeederConnectionOverride['es_protocol'])) {
			return $config->elasticsearchFeederConnectionOverride['es_protocol'];
		}

		return $this->get('es_protocol');
	}

	public function getElasticSearchIndexName(Template $template)
	{
		return $this->get("es_template_indexname_$template->id");
	}

	protected function isDebugModeEnabled()
	{
		$debugMode = $this->get('es_debug_mode');
		return (isset($debugMode) && $debugMode === 'on') ? true : false;
	}

	public function indexAllAllowedPages(string $templateId = '')
	{
		$templates = $this->wire('templates');
		$pages = $this->wire('pages');
		$config = $this->wire('config');

		$allowedTemplates = [];
		foreach ($templates as $template) {

			if (empty($templateId) && $this->checkAllowedTemplate($template)) { // check for all templates
				array_push($allowedTemplates, $template->id);
			} else if (!empty($templateId) && $templateId == $template->id && $this->checkAllowedTemplate($template)) { // check only one template
				array_push($allowedTemplates, $template->id);
			}
		}

		if (count($allowedTemplates) > 0) {
			$allowedTemplates = implode('|', $allowedTemplates);
			$pagesToIndex = $pages->find("template=$allowedTemplates");

			foreach ($pagesToIndex as $page) {

				$success = $this->sendDocumentToElasticSearch($page);

				if ($success) {
					$page->meta($this->elasticSearchMetaKeyName, [
						"es_id" => $success,
						"lastindex" => date('c')
					]);

					// output log in CLI batch import script
					if ($config->cli) {
						echo "Page \"$page->title\" sent to ElasticSearch\n";
					}
				}
			}

			return "All pages sent to ElasticSearch.\n";
		} else {
			return "No template choosen. You have to allow at least one template for the index.\n";
		}
	}
}
