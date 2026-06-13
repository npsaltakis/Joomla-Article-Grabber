<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Model
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\Database\ParameterType;
use Nickpsal\Component\ContentApiGrabber\Administrator\Helper\ArticleImporter;
use Nickpsal\Component\ContentApiGrabber\Administrator\Helper\CryptHelper;
use Nickpsal\Component\ContentApiGrabber\Administrator\Helper\RestHelper;

/**
 * Model for browsing and pulling articles from configured remote sources.
 */
class RemoteModel extends FormModel
{
    /**
     * Load the pull form (target category + author + state).
     *
     * @param   array    $data      Data.
     * @param   boolean  $loadData  Load data flag.
     *
     * @return  \Joomla\CMS\Form\Form|false
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_content_api_grabber.pull',
            'pull',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        return $form ?: false;
    }

    /**
     * Default author = current user.
     *
     * @return  array
     */
    protected function loadFormData()
    {
        return ['created_by' => (int) Factory::getApplication()->getIdentity()->id];
    }

    /**
     * Get the published sources for the picker.
     *
     * @return  array
     */
    public function getSources(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name', 'url', 'default_catid']))
            ->from($db->quoteName('#__cgrabber_sources'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');

        return (array) $db->setQuery($query)->loadObjectList();
    }

    /**
     * Load a single source row with the decrypted token.
     *
     * @param   int  $id  Source id.
     *
     * @return  object|null  Object with ->url and ->token (plaintext), or null.
     */
    public function getSource(int $id): ?object
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__cgrabber_sources'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $row = $db->setQuery($query)->loadObject();

        if (!$row) {
            return null;
        }

        $row->token = CryptHelper::decrypt((string) $row->token_enc);
        unset($row->token_enc);

        return $row;
    }

    /**
     * Fetch a page of articles from a remote source.
     *
     * @param   int  $sourceId  Source id.
     * @param   int  $limit     Page size.
     * @param   int  $offset    Offset.
     *
     * @return  array  Normalized list: [{id, title, state, created}]
     *
     * @throws  \RuntimeException
     */
    public function getRemoteArticles(int $sourceId, int $limit = 50, int $offset = 0): array
    {
        $source = $this->getSource($sourceId);

        if (!$source) {
            throw new \RuntimeException(\Joomla\CMS\Language\Text::_('COM_CONTENT_API_GRABBER_ERROR_SOURCE_NOT_FOUND'));
        }

        $result = RestHelper::listArticles($source->url, $source->token, $limit, $offset);

        $items = [];

        foreach ($result['items'] as $res) {
            $a       = $res->attributes ?? null;
            $items[] = (object) [
                'id'      => (int) ($res->id ?? ($a->id ?? 0)),
                'title'   => (string) ($a->title ?? ''),
                'state'   => (int) ($a->state ?? 0),
                'created' => (string) ($a->created ?? ''),
            ];
        }

        return $items;
    }

    /**
     * Pull selected remote articles into the local site.
     *
     * @param   int    $sourceId    Source id.
     * @param   int[]  $articleIds  Remote article ids to pull.
     * @param   int    $catid       Target category.
     * @param   int    $userId      Author.
     * @param   int    $state       Published state.
     *
     * @return  array  ['ok' => int, 'failed' => int, 'messages' => string[]]
     *
     * @throws  \RuntimeException
     */
    public function pull(int $sourceId, array $articleIds, int $catid, int $userId, int $state): array
    {
        $source = $this->getSource($sourceId);

        if (!$source) {
            throw new \RuntimeException(\Joomla\CMS\Language\Text::_('COM_CONTENT_API_GRABBER_ERROR_SOURCE_NOT_FOUND'));
        }

        $ok       = 0;
        $failed   = 0;
        $messages = [];

        foreach ($articleIds as $remoteId) {
            $remoteId = (int) $remoteId;

            try {
                $resource = RestHelper::getArticle($source->url, $source->token, $remoteId);
                $article  = $this->normalize($resource);

                // Images are absolutized against the remote site root.
                $result = ArticleImporter::import($article, $catid, $userId, $state, $source->url);

                $this->logPull($source, $remoteId, $result, $userId);

                $ok++;
                $messages[] = $article['title'] . ' → #' . $result['article_id']
                    . ' (' . $result['images_ok'] . ' img)';
            } catch (\Throwable $e) {
                $failed++;
                $messages[] = 'ID ' . $remoteId . ': ' . $e->getMessage();
                Log::add('Joomla Article Grabber pull failed (id ' . $remoteId . '): ' . $e->getMessage(), Log::WARNING, 'com_content_api_grabber');
            }
        }

        return ['ok' => $ok, 'failed' => $failed, 'messages' => $messages];
    }

    /**
     * Normalize a JSON:API article resource into the ArticleImporter payload.
     *
     * @param   object  $resource  The remote resource.
     *
     * @return  array
     */
    private function normalize(object $resource): array
    {
        $a   = $resource->attributes ?? new \stdClass();
        $img = (array) ($a->images ?? []);

        $intro = (string) ($a->introtext ?? '');
        $full  = (string) ($a->fulltext ?? '');

        // The list/standard API returns a combined "text"; use it when intro/full are empty.
        if ($intro === '' && $full === '' && !empty($a->text)) {
            $intro = (string) $a->text;
        }

        return [
            'title'     => (string) ($a->title ?? ''),
            'alias'     => (string) ($a->alias ?? ''),
            'language'  => (string) ($a->language ?? '*') ?: '*',
            'introtext' => $intro,
            'fulltext'  => $full,
            'metakey'   => (string) ($a->metakey ?? ''),
            'metadesc'  => (string) ($a->metadesc ?? ''),
            'images'    => [
                'image_intro'            => (string) ($img['image_intro'] ?? ''),
                'image_intro_alt'        => (string) ($img['image_intro_alt'] ?? ''),
                'image_intro_caption'    => (string) ($img['image_intro_caption'] ?? ''),
                'image_fulltext'         => (string) ($img['image_fulltext'] ?? ''),
                'image_fulltext_alt'     => (string) ($img['image_fulltext_alt'] ?? ''),
                'image_fulltext_caption' => (string) ($img['image_fulltext_caption'] ?? ''),
            ],
        ];
    }

    /**
     * Record a pull in #__cgrabber_log.
     *
     * @return  void
     */
    private function logPull(object $source, int $remoteId, array $result, int $userId): void
    {
        try {
            $db  = $this->getDatabase();
            $row = (object) [
                'source_url'        => $source->url,
                'source_id'         => $remoteId,
                'target_article_id' => $result['article_id'],
                'title'             => $result['title'],
                'imported_by'       => $userId,
                'images_ok'         => $result['images_ok'],
                'images_failed'     => $result['images_failed'],
                'created'           => Factory::getDate()->toSql(),
            ];

            $db->insertObject('#__cgrabber_log', $row);
        } catch (\Throwable $e) {
            Log::add('Joomla Article Grabber log insert failed: ' . $e->getMessage(), Log::WARNING, 'com_content_api_grabber');
        }
    }
}
