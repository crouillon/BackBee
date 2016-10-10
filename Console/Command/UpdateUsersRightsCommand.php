<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee Standard Edition.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee Standard Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee Standard Edition. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Console\Command;

use BackBee\BBApplication;
use BackBee\Console\AbstractCommand;
use BackBee\Exception\BBException;
use BackBee\Security\Group;
use BackBee\Security\Acl\Permission\MaskBuilder;
use BackBee\Security\User;

use Doctrine\ORM\Tools\SchemaTool;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * Update base users right command
 * @author Adrien Loiseau <aloiseau@nextinteractive.fr>
 */
class UpdateUsersRightsCommand extends AbstractCommand
{
    /**
     * The current entity manager
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * The current BackBee application
     * @var \BackBee\BBApplication
     */
    private $bbapp;

    /**
     * ACL provider object
     * @var \Symfony\Component\Security\Acl\Dbal\MutableAclProvider
     */
    private $aclProvider;

    /**
     * Users/groups/rights tables
     * @var array
     */
    private $tables = [
        'user_group',
        'group',
        'user',
        'acl_classes',
        'acl_entries',
        'acl_object_identities',
        'acl_object_identity_ancestors',
        'acl_security_identities'
    ];

    /**
     * Class content manager
     * @var \BackBee\ClassContent\ClassContentManager
     */
    private $classContentManager;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('users:update_rights')
            ->setDescription('Update users rights')
            ->addOption('clean', null, InputOption::VALUE_OPTIONAL, 'Cleaning all tables', false)
            ->addOption('memory-limit', 'm', InputOption::VALUE_OPTIONAL, 'The memory limit to set.')
            # New user informations
            ->addOption('user_name', 'user_name', InputOption::VALUE_OPTIONAL, 'username.')
            ->addOption('user_password', 'user_password', InputOption::VALUE_OPTIONAL, 'user password.')
            ->addOption('user_email', 'user_email', InputOption::VALUE_OPTIONAL, 'user email.')
            ->addOption('user_firstname', 'user_firstname', InputOption::VALUE_OPTIONAL, 'user firstname.')
            ->addOption('user_lastname', 'user_lastname', InputOption::VALUE_OPTIONAL, 'user lastname.')
            ->addOption('user_group', 'user_group', InputOption::VALUE_OPTIONAL, 'user group.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startingTime = microtime(true);

        $this->bbapp = $this->getContainer()->get('bbapp');
        $this->em = $this->bbapp->getEntityManager();

        $this->output = $output;
        if ($input->getOption('verbose')) {
            $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        }

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->aclProvider = $this->bbapp->getSecurityContext()->getACLProvider();
        $this->classContentManager = $this->bbapp->getContainer()->get('classcontent.manager')
                                                                 ->setBBUserToken($this->bbapp->getBBUserToken());

        if (null !== $input->getOption('memory-limit')) {
            ini_set('memory_limit', $input->getOption('memory-limit'));
        }

        $outputCmd = '';
        $outputCmd .=  ($input->getOption('memory-limit') !== null) ? ' --m='. $input->getOption('memory-limit') : '';
        $outputCmd .=  ($input->getOption('user_name') !== null) ? ' --user_name='. $input->getOption('user_name') : '';
        $outputCmd .=  ($input->getOption('user_password') !== null) ? ' --user_password='. $input->getOption('user_password') : '';
        $outputCmd .=  ($input->getOption('user_email') !== null) ? ' --user_email='. $input->getOption('user_email') : '';
        $outputCmd .=  ($input->getOption('user_firstname') !== null) ? ' --user_firstname='. $input->getOption('user_firstname') : '';
        $outputCmd .=  ($input->getOption('user_lastname') !== null) ? ' --user_lastname='. $input->getOption('user_lastname') : '';
        $outputCmd .=  ($input->getOption('user_group') !== null) ? ' --user_group='. $input->getOption('user_group') : '';
        $outputCmd .=  ($input->getOption('verbose')) ? ' --v' : '';

        $this->writeln(sprintf('BEGIN : users:update_rights %s', $outputCmd), OutputInterface::VERBOSITY_NORMAL);

        // Récupère le fichier users_rights.yml
        $usersRights = $this->bbapp->getConfig()->getGroupsConfig();

        if (null === $this->aclProvider) {
            throw new \InvalidArgumentException('None ACL provider found');
        }

        // Vérifier que les groups de droit sont bien définis
        if (false === is_array($usersRights)) {
            throw new \InvalidArgumentException('Malformed groups.yml file, aborting');
        }

        if($this->checksBackBeeVersion()) {
            // Vérification et mise à jour de la structure de la table user
            $this->checksUserTable();

            // Vérification et mise à jour de la structure de la table group (anciennement groups)
            $this->checksGroupTable();
        }

        // Traitement de l'option clean
        if ($input->getOption('clean')) {
            $this->writeln("\n" . '<info>[Cleaning all tables]</info>' . "\n");
            $this->cleanTables();
            $this->writeln(sprintf('Cleaning done in %d s.', microtime(true) - $startingTime));
        }

        $this->writeln("\n" . '<info>[Check ACL tables existence]</info>' . "\n");
        $this->checkAclTables();

        // Update des droits
        $this->writeln("\n" . '<info>[Updating users rights]</info>' . "\n");
        $this->updateRights($usersRights);

        // Update des utilisateurs
        $this->writeln("\n" . '<info>[Updating/Creating user]</info>' . "\n");
        $this->updateUsers($usersRights, $input);

        $this->writeln(sprintf('<info>Update done in %d s.</info>', microtime(true) - $startingTime), OutputInterface::VERBOSITY_NORMAL);
    }

    /**
     * Create ACL tables if doesn't exit
     */
    private function checkAclTables()
    {
        $dropTableSql = [];

        $schemaManager = $this->em->getConnection()->getSchemaManager();
        // Create security Acl tables
        $tablesMapping = [
            'class_table_name'         => 'acl_classes',
            'entry_table_name'         => 'acl_entries',
            'oid_table_name'           => 'acl_object_identities',
            'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
            'sid_table_name'           => 'acl_security_identities',
        ];

        foreach ($tablesMapping as $key => $value) {
            if ($schemaManager->tablesExist(array($value)) === true) {
                $dropTableSql[] = 'DROP TABLE IF EXISTS `' . $value. '`;';
            }
        }

        if (count($dropTableSql) != count($tablesMapping)) {
            if (!empty($dropTableSql)) {
                $this->em->getConnection()->executeUpdate('SET FOREIGN_KEY_CHECKS=0');
                $this->em->getConnection()->executeQuery(implode(' ', $dropTableSql));
                $this->em->getConnection()->executeUpdate('SET FOREIGN_KEY_CHECKS=1');
            }

            $schema = new \Symfony\Component\Security\Acl\Dbal\Schema($tablesMapping);
            $platform = $this->em->getConnection()->getDatabasePlatform();

            foreach ($schema->toSql($platform) as $query) {
                $this->em->getConnection()->executeQuery($query);
            }

            $this->writeln(sprintf('ACL tables recreated.', $value));
        } else {
            $this->writeln(sprintf('All ACL tables exists.', $value));
        }
    }

    /**
     * Checks for BackBee version, at least 1.1.0 is required
     *
     * @return UpdateUsersRightsCommand
     */
    private function checksBackBeeVersion()
    {
        $this->writeln('<info>Checking BackBee instance</info>');
        $this->writeln(sprintf(' - BackBee version: %s - ', BBApplication::VERSION));

        if (0 > version_compare(BBApplication::VERSION, '1.1')) {
            $this->writeln("<error>Failed</error>");
            throw new BBException(sprintf('This command needs at least BackBee v1.1.0 installed, gets BackBee v%s.%sPlease upgrade your distribution.', BBApplication::VERSION, PHP_EOL));
        }

        $this->writeln('<info>OK</info>');
        return $this;
    }

    /**
     * Checks for existing table `user`
     */
    private function checksUserTable()
    {
        $schemaManager = $this->em->getConnection()->getSchemaManager();
        $tableName = $this->em->getClassMetadata('BackBee\Security\User')->getTableName();
        $this->writeln(sprintf('<info>Checking %s table</info>', $tableName));
        $this->write(sprintf(' - Existing table `%s` - ', $tableName));

        if (false === $schemaManager->tablesExist([$tableName])) {
            $this->writeln("<error>Failed</error>");
            throw new BBException(sprintf('Table `%s` does not exist. Cannot upgrade database storage anymore.', $tableName));
        }

        $sectionMeta = $this->em->getClassMetadata('BackBee\Security\User');

        $requiredFields['id'] = $sectionMeta->getColumnName('_id');
        $requiredFields['login'] = $sectionMeta->getColumnName('_login');
        $requiredFields['email'] = $sectionMeta->getColumnName('_email');
        $requiredFields['password'] = $sectionMeta->getColumnName('_password');
        $requiredFields['state'] = $sectionMeta->getColumnName('_state');
        $requiredFields['activated'] = $sectionMeta->getColumnName('_activated');
        $requiredFields['firstname'] = $sectionMeta->getColumnName('_firstname');
        $requiredFields['lastname'] = $sectionMeta->getColumnName('_lastname');
        $requiredFields['api_key_public'] = $sectionMeta->getColumnName('_api_key_public');
        $requiredFields['api_key_private'] = $sectionMeta->getColumnName('_api_key_private');
        $requiredFields['api_key_enabled'] = $sectionMeta->getColumnName('_api_key_enabled');
        $requiredFields['created'] = $sectionMeta->getColumnName('_created');
        $requiredFields['modified'] = $sectionMeta->getColumnName('_modified');

        $existingFields = array_keys($schemaManager->listTableColumns($tableName));
        $missingFields = array_diff($requiredFields, $existingFields);

        if (empty($missingFields)) {
            $this->writeln('<info>OK</info>');
            return;
        }

        if (1 < count($missingFields)) {
            $this->writeln("<error>Failed</error>");
            $this->updateUserTable();
        }
    }

    /**
     * Updates the table user
     *
     */
    private function updateUserTable()
    {
        $this->em->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=0');

        $schemaTool = new SchemaTool($this->em);
        $sectionMeta = $this->em->getClassMetadata('BackBee\Security\User');
        $tableLayout = $sectionMeta->getTableName();

        $this->writeln(sprintf('<info>Updating table `%s`</info>',$tableLayout));
        $this->write(sprintf(' - Structure of table `%s` updated - ', $tableLayout));
        $schemaTool->updateSchema(array($sectionMeta), true);

        $this->em->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=1');

        $this->writeln('<info>OK</info>');
    }

    /**
     * Checks for existing table `group`
     */
    private function checksGroupTable()
    {
        $schemaManager = $this->em->getConnection()->getSchemaManager();
        $tableName = $this->em->getClassMetadata('BackBee\Security\Group')->getTableName();
        $this->writeln(sprintf('<info>Checking %s table</info>', $tableName));
        $this->write(sprintf(' - Existing table `%s` - ', $tableName));

        if (true === $schemaManager->tablesExist(['groups'])) {
            $this->writeln("<error>Failed</error> : Start rename `groups` table");
            $this->write(" - Rename `groups` table - ");


            $this->em->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=0');
            $this->em->getConnection()->executeQuery('DROP TABLE `group`');
            $this->em->getConnection()->executeQuery('RENAME TABLE `groups` TO `' . $tableName.'`');
            $this->em->getConnection()->executeQuery('ALTER TABLE `'. $tableName.'` CHANGE `identifier` `description` VARCHAR(255);');

            $this->em->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=1');
            if (false === $schemaManager->tablesExist([$tableName])) {
                $this->writeln("<error>Failed : Renaming table `groups`</error>");
                throw new BBException(sprintf('Table `%s` does not exist. Cannot upgrade database storage anymore.', $tableName));
            }
        }

        $sectionMeta = $this->em->getClassMetadata('BackBee\Security\Group');

        $requiredFields['id'] = $sectionMeta->getColumnName('_id');
        $requiredFields['name'] = $sectionMeta->getColumnName('_name');
        $requiredFields['description'] = $sectionMeta->getColumnName('_description');
        $requiredFields['site_uid'] = $sectionMeta->getSingleAssociationJoinColumnName('_site');

        $existingFields = array_keys($schemaManager->listTableColumns($tableName));
        $missingFields = array_diff($requiredFields, $existingFields);

        if (empty($missingFields)) {
            $this->writeln('<info>OK</info>');
            return;
        }

        if (1 < count($missingFields)) {
            $this->writeln("<error>Failed : Missing fields</error>");
        }
    }


    /**
     * Clean all tables list in $this->tables
     */
    private function cleanTables()
    {
        foreach ($this->tables as $table) {
            $this->em->getConnection()->executeQuery("DELETE FROM `$table`");
        }
    }

    /**
     * Update de la table group
     */
    private function updateRights($usersRights)
    {
        if (true === is_array($usersRights)) {
            $this->writeln('<info>- Updating groups: </info>' . "\n");

            $this->em->getConnection()->executeQuery('DELETE FROM `acl_classes` WHERE 1=1');
            $this->em->getConnection()->executeQuery('DELETE FROM `acl_entries` WHERE 1=1');
            $this->em->getConnection()->executeQuery('DELETE FROM `acl_object_identities` WHERE 1=1');
            $this->em->getConnection()->executeQuery('DELETE FROM `acl_object_identity_ancestors` WHERE 1=1');
            $this->em->getConnection()->executeQuery('DELETE FROM `acl_security_identities` WHERE 1=1');

            // First create all groups
            foreach ($usersRights as $group_identifier => $rights) {
                $this->writeln(sprintf('Checking group: %s', $group_identifier));

                // Création du group si introuvable
                if (null === $group = $this->em
                        ->getRepository('BackBee\Security\Group')
                        ->findOneBy(array('_name' => $group_identifier))) {

                    // ensure group exists
                    $group = new Group();
                    $group->setDescription(isset($rights['description']) ? $rights['description'] : $group_identifier)
                          ->setName($group_identifier);

                    $this->em->persist($group);
                    $this->em->flush($group);
                    $this->writeln(sprintf("\t- New group created: `%s`", $group_identifier));
                }
            }

            // Then apply rights
            foreach ($usersRights as $group_identifier => $rights) {
                $this->writeln(sprintf('Treating group: %s', $group_identifier));
                $securityIdentity = new UserSecurityIdentity($group->getObjectIdentifier(), 'BackBee\Security\Group');

                // Sites
                if (true === array_key_exists('sites', $rights)) {
                    $sites = $this->addSiteRights($rights['sites'], $this->aclProvider, $securityIdentity);

                    // Layouts
                    if (true === array_key_exists('layouts', $rights)) {
                        $this->addLayoutRights($rights['layouts'], $sites, $this->aclProvider, $securityIdentity);
                        $this->writeln("\t- Rights set on sites and layouts for group");
                    }

                    // Pages
                    if (true === array_key_exists('pages', $rights)) {
                        $this->addPageRights($rights['pages'], $this->aclProvider, $securityIdentity);
                        $this->writeln("\t- Rights set on pages for group");
                    }

                    // Mediafolders
                    if (true === array_key_exists('mediafolders', $rights)) {
                        $this->addFolderRights($rights['mediafolders'], $this->aclProvider, $securityIdentity);
                        $this->writeln("\t- Rights set on library folders for group");
                    }

                    // Contents
                    if (true === array_key_exists('contents', $rights)) {
                        $this->addContentRights($rights['contents'], $this->aclProvider, $securityIdentity);
                        $this->writeln("\t- Rights set on contents for group");
                    }

                    // Bundles
                    if (true === array_key_exists('bundles', $rights)) {
                        $this->addBundleRights($rights['bundles'], $this->aclProvider, $securityIdentity);
                        $this->writeln("\t- Rights set on bundles for group");
                    }

                    // Groups
                    if (true === array_key_exists('groups', $rights)) {
                        $this->addGroupRights($rights['groups'], $this->aclProvider, $securityIdentity);
                        $this->writeln("\t- Rights set on groups for group");
                    }

                    // Users
                    if (true === array_key_exists('users', $rights)) {
                        $this->addUserRights($rights['users'], $this->aclProvider, $securityIdentity);
                        $this->writeln("\t- Rights set on users for group");
                    }

                } else {
                    $this->writeln(sprintf("\t- No site rights defined for %s group, skip", $group_identifier));
                }
            }
        }
    }

    /**
     * Update de la table user
     */
    private function updateUsers($userRights, $input)
    {

        if (!is_array($userRights)) {
            return;
        }
        if (!$input->getOption('user_name') || !$input->getOption('user_password') || !$input->getOption('user_email')) {
            return;
        }

        if (null === $user = $this->em->getRepository('BackBee\Security\User')->findOneBy(array('_login' => $input->getOption('user_name')))) {
            $encoderFactory = $this->bbapp->getContainer()->get('security.context')->getEncoderFactory();

            $user = new User($input->getOption('user_name'));

            $encoder = $encoderFactory->getEncoder($user);

            $user
                ->setPassword($encoder->encodePassword($input->getOption('user_password'), ''))
                ->setEmail($input->getOption('user_email'))
                ->setLastname($input->getOption('user_firstname'))
                ->setFirstname($input->getOption('user_lastname'))
                ->setApiKeyEnabled(true)
                ->setActivated(true)
            ;
            $user->generateRandomApiKey();
            $user->setApiKeyEnabled(true);
            $this->em->persist($user);
            $this->em->flush($user);
        }

        if ($input->getOption('user_group')) {
            if (null !== $group = $this->em->getRepository('BackBee\Security\Group')
                    ->findOneBy(array('_name' => $input->getOption('user_group')))) {
                if (false === $group->getUsers()->indexOf($user)) {
                    $group->addUser($user);
                    $this->em->persist($group);
                    $this->em->flush($group);
                }
            } else {
                $this->writeln(sprintf('Warning: unknown group %s', $input->getOption('user_group')));
            }
        }

        $this->em->persist($user);
        $this->em->flush($user);
     
        $this->writeln("\t- ".sprintf ("|%-20s|", $input->getOption('user_name')).": ".$input->getOption('user_password'));
    }

    /**
     * Update de la table site
     */
    private function addSiteRights($sites_def, $aclProvider, $securityIdentity)
    {
        if (false === array_key_exists('resources', $sites_def) || false === array_key_exists('actions', $sites_def)) {
            return array();
        }

        $actions = $this->getActions($sites_def['actions']);
        if (0 === count($actions)) {
            $this->writeln("\t- No actions defined on site");
            return array();
        }

        $sites = array();
        if (true === is_array($sites_def['resources'])) {

            foreach ($sites_def['resources'] as $site_label) {
                if (null === $site = $this->em->getRepository('BackBee\Site\Site')->findOneBy(array('_label' => $site_label))) {
                    $this->writeln(sprintf("\t- Unknown site with label %s, skip", $site_label));
                    continue;
                }

                $sites[] = $site;
                $this->addObjectAcl($site, $aclProvider, $securityIdentity, $actions);
            }
        } elseif ('all' === $sites_def['resources']) {
            $sites = $this->em->getRepository('BackBee\Site\Site')->findAll();
            $this->addClassAcl('BackBee\Site\Site', $aclProvider, $securityIdentity, $actions);
        }

        return $sites;
    }

    /**
     * Update de la table layout
     */
    private function addLayoutRights($layout_def, $sites, $aclProvider, $securityIdentity)
    {
        if (false === array_key_exists('resources', $layout_def) || false === array_key_exists('actions', $layout_def)) {
            return null;
        }

        $actions = $this->getActions($layout_def['actions']);
        if (0 === count($actions)) {
            $this->writeln("\t- No actions defined on layout");
            return array();
        }

        foreach ($sites as $site) {
            if (true === is_array($layout_def['resources'])) {
                foreach ($layout_def['resources'] as $layout_label) {
                    if (null === $layout = $this->em->getRepository('BackBee\Site\Layout')->findOneBy(array('_site' => $site, '_label' => $layout_label))) {
                        $this->writeln(sprintf("\t- Unknown layout with label %s for site %s, skip", $layout_label, $site->getLabel()));
                        continue;
                    }

                    $this->addObjectAcl($layout, $aclProvider, $securityIdentity, $actions);
                }
            } elseif ('all' === $layout_def['resources']) {
                $this->addClassAcl('BackBee\Site\Layout', $aclProvider, $securityIdentity, $actions);
            }
        }
    }

    /**
     * Update de la table page
     */
    private function addPageRights($page_def, $aclProvider, $securityIdentity)
    {
        if (false === array_key_exists('resources', $page_def) || false === array_key_exists('actions', $page_def)) {
            return null;
        }

        $actions = $this->getActions($page_def['actions']);
        if (0 === count($actions)) {
            $this->writeln("\t- No actions defined on page");
            return array();
        }

        if (true === is_array($page_def['resources'])) {
            foreach ($page_def['resources'] as $page_url) {
                $pages = $this->em->getRepository('BackBee\NestedNode\Page')->findBy(array('_url' => $page_url));
                foreach ($pages as $page) {
                    $this->addObjectAcl($page, $aclProvider, $securityIdentity, $actions);
                }
            }
        } elseif ('all' === $page_def['resources']) {
            $this->addClassAcl('BackBee\NestedNode\Page', $aclProvider, $securityIdentity, $actions);
        }
    }

    /**
     * Update de la table page
     */
    private function addFolderRights($folder_def, $aclProvider, $securityIdentity)
    {
        if (false === array_key_exists('resources', $folder_def) || false === array_key_exists('actions', $folder_def)) {
            return null;
        }

        $actions = $this->getActions($folder_def['actions']);
        if (0 === count($actions)) {
            $this->writeln("\t- No actions defined on folder");
            return array();
        }

        if ('all' === $folder_def['resources']) {
            $this->addClassAcl('BackBee\NestedNode\MediaFolder', $aclProvider, $securityIdentity, $actions);
        }
    }

    private function addContentRights($content_def, $aclProvider, $securityIdentity)
    {
        if (false === array_key_exists('resources', $content_def) || false === array_key_exists('actions', $content_def)) {
            return null;
        }

        if ('all' === $content_def['resources']) {
            $actions = $this->getActions($content_def['actions']);
            if (0 === count($actions)) {
                $this->writeln("\t- No actions defined on content");
                return array();
            }

            $this->addClassAcl('BackBee\ClassContent\AbstractClassContent', $aclProvider, $securityIdentity, $actions);
        } elseif (true === is_array($content_def['resources']) && 0 < count($content_def['resources'])) {
            if (true === is_array($content_def['resources'][0])) {
                $used_classes = array();
                foreach($content_def['resources'] as $index => $resources_def) {
                    if (false === isset($content_def['actions'][$index])) {
                        continue;
                    }

                    $actions = $this->getActions($content_def['actions'][$index]);

                    if ('remains' === $resources_def) {
                        foreach ($this->classContentManager->getAllClassContentClassnames() as $class) {
                            if (false === in_array($class, $used_classes)) {
                                $used_classes[] = $class;
                                if (0 < count($actions)) {
                                    $this->addClassAcl($class, $aclProvider, $securityIdentity, $actions);
                                }
                            }
                        }
                    } elseif (true === is_array($resources_def)) {
                        foreach ($resources_def as $content) {
                            $classname = '\BackBee\ClassContent\\' . $content;
                            if (substr($classname, -1) === '*') {
                                $classname = substr($classname, 0 - 1);
                                foreach ($this->classContentManager->getAllClassContentClassnames() as $class) {
                                    if (0 === strpos($class, $classname)) {
                                        $used_classes[] = $class;
                                        if (0 < count($actions)) {
                                            $this->addClassAcl($class, $aclProvider, $securityIdentity, $actions);
                                        }
                                    }
                                }
                            } elseif (true === class_exists($classname)) {
                                $used_classes[] = $classname;
                                if (0 < count($actions)) {
                                    $this->addClassAcl($classname, $aclProvider, $securityIdentity, $actions);
                                }
                            } else {
                                $this->writeln(sprintf("\t- Unknown class content %s, skip", $classname));
                            }
                        }
                    }
                }
            } else {
                $actions = $this->getActions($content_def['actions']);
                if (0 === count($actions)) {
                    $this->writeln("\t- No actions defined on content");
                    return array();
                }

                foreach ($content_def['resources'] as $content) {
                    $classname = '\BackBee\ClassContent\\' . $content;
                    if (substr($classname, -1) === '*') {
                        $classname = substr($classname, 0 -1);
                        foreach ($this->classContentManager->getAllClassContentClassnames() as $class) {
                            if (0 === strpos($class, $classname)) {
                                $this->addClassAcl($class, $aclProvider, $securityIdentity, $actions);
                            }
                        }
                    } elseif (true === class_exists($classname)) {
                        $this->addClassAcl($classname, $aclProvider, $securityIdentity, $actions);
                    } else {
                        $this->writeln(sprintf("\t- Unknown class content %s, skip", $classname));
                    }
                }
            }
        }
    }

    private function addBundleRights($bundle_def, $aclProvider, $securityIdentity)
    {
        if (false === array_key_exists('resources', $bundle_def) || false === array_key_exists('actions', $bundle_def)) {
            return null;
        }

        $actions = $this->getActions($bundle_def['actions']);
        if (0 === count($actions)) {
            $this->writeln('Notice: none actions defined on bundle' . PHP_EOL);
            return array();
        }

        if (true === is_array($bundle_def['resources'])) {
            foreach ($bundle_def['resources'] as $bundle_name) {
                if (null !== $bundle = $this->bbapp->getBundle($bundle_name)) {
                    $this->addObjectAcl($bundle, $aclProvider, $securityIdentity, $actions);
                }
            }
        } elseif ('all' === $bundle_def['resources']) {
            foreach ($this->bbapp->getBundles() as $bundle) {
                $this->addObjectAcl($bundle, $aclProvider, $securityIdentity, $actions);
            }
        }
    }

    private function addGroupRights($group_ref, $aclProvider, $securityIdentity)
    {
        if (false === array_key_exists('resources', $group_ref) || false === array_key_exists('actions', $group_ref)) {
            return null;
        }

        $actions = $this->getActions($group_ref['actions']);
        if (0 === count($actions)) {
            $this->writeln('Notice: none actions defined on group' . PHP_EOL);
            return array();
        }

        if (true === is_array($group_ref['resources'])) {
            foreach ($group_ref['resources'] as $group_name) {
                try {
                    $group = $this->em->getRepository('BackBee\Security\Group')->findOneBy(['_name' => $group_name]);
                } catch (\Exception $e) {
                    $group = $this->em->getRepository('BackBee\Security\Group')->find($group_name);
                }

                if (null !== $group) {
                    $this->addObjectAcl($group, $aclProvider, $securityIdentity, $actions);
                }
            }
        } elseif ('all' === $group_ref['resources']) {
            $this->addClassAcl('BackBee\Security\Group', $aclProvider, $securityIdentity, $actions);
        }
    }

    private function addUserRights($user_ref, $aclProvider, $securityIdentity)
    {
        if (false === array_key_exists('resources', $user_ref) || false === array_key_exists('actions', $user_ref)) {
            return null;
        }

        $actions = $this->getActions($user_ref['actions']);
        if (0 === count($actions)) {
            $this->writeln('Notice: none actions defined on user' . PHP_EOL);
            return array();
        }

        if (true === is_array($user_ref['resources'])) {
            foreach ($user_ref['resources'] as $username) {
                try {
                    $user = $this->em->getRepository('BackBee\Security\User')->findOneBy(['_login' => $username]);
                } catch (\Exception $e) {
                    $user = $this->em->getRepository('BackBee\Security\User')->find($username);
                }

                if (null !== $user) {
                    $this->addObjectAcl($user, $aclProvider, $securityIdentity, $actions);
                }
            }
        } elseif ('all' === $user_ref['resources']) {
            $this->addClassAcl('BackBee\Security\User', $aclProvider, $securityIdentity, $actions);
        }
    }

    private function getActions($def)
    {
        $actions = array();
        if (true === is_array($def)) {
            $actions = array_intersect(array('view', 'create', 'edit', 'delete', 'publish'), $def);
        } elseif ('all' === $def) {
            $actions = array('view', 'create', 'edit', 'delete', 'publish');
        }

        return $actions;
    }

    private function addClassAcl($className, $aclProvider, $securityIdentity, $rights)
    {
        $objectIdentity = new ObjectIdentity('all', $className);
        $this->addAcl($objectIdentity, $aclProvider, $securityIdentity, $rights);
    }

    private function addObjectAcl($object, $aclProvider, $securityIdentity, $rights)
    {
        $objectIdentity = ObjectIdentity::fromDomainObject($object);
        $this->addAcl($objectIdentity, $aclProvider, $securityIdentity, $rights);
    }

    private function addAcl(ObjectIdentity $objectIdentity, $aclProvider, $securityIdentity, $rights)
    {
        try {
            // Getting ACL for this object identity
            try {
                $acl = $aclProvider->createAcl($objectIdentity);
            } catch (\Exception $e) {
                $acl = $aclProvider->findAcl($objectIdentity);
            }

            // Calculating mask
            $builder = new MaskBuilder();
            foreach ($rights as $right) {
                $builder->add($right);
            }
            $mask = $builder->get();

            // first revoke existing access for this security identity
            foreach($acl->getObjectAces() as $i => $ace) {
                if($securityIdentity->equals($ace->getSecurityIdentity())) {
                    $acl->updateObjectAce($i, $ace->getMask() & ~$mask);
                }
            }

            // then grant
            if ('all' === $objectIdentity->getIdentifier()) {
                $acl->insertClassAce($securityIdentity, $mask);
            } else {
                $acl->insertObjectAce($securityIdentity, $mask);
            }
            $aclProvider->updateAcl($acl);
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage(), 0, $e);
        }
    }

    /**
     * Writes a message to the output and adds a newline at the end.
     *
     * @param string|array $messages The message as an array of lines or a single string
     * @param int          $verbose     The verbosity of output (one of the VERBOSITY constants)
     *
     * @throws \InvalidArgumentException When unknown output type is given
     */
    protected function writeln($messages, $verbose = OutputInterface::VERBOSITY_NORMAL)
    {
        $this->write($messages, true, $verbose);
    }    

    /**
     * Writes a message to the output
     *
     * @param string|array $messages The message as an array of lines or a single string
     * @param bool         $newline  Whether to add a newline
     * @param int          $verbose     The verbosity of output (one of the VERBOSITY constants)
     *
     * @throws \InvalidArgumentException When unknown output type is given
     */
    protected function write($affichage, $newLine = false, $verbose = OutputInterface::VERBOSITY_VERBOSE)
    {
        switch ($verbose) {
            case OutputInterface::VERBOSITY_NORMAL:
                if ($newLine)
                    $this->output->writeln($affichage);
                else
                    $this->output->write($affichage);
                break;

            case OutputInterface::VERBOSITY_VERBOSE:
                if ($this->output->isVerbose()) {
                    if ($newLine)
                        $this->output->writeln($affichage);
                    else
                        $this->output->write($affichage);
                }
                break;

            case OutputInterface::VERBOSITY_VERY_VERBOSE :
                if ($this->output->isVeryVerbose()) {
                    if ($newLine)
                        $this->output->writeln($affichage);
                    else
                        $this->output->write($affichage);
                }
                break;

            case OutputInterface::VERBOSITY_DEBUG :
                if ($this->output->isDebug()) {
                    if ($newLine)
                        $this->output->writeln($affichage);
                    else
                        $this->output->write($affichage);
                }
                break;
        }
    }
}