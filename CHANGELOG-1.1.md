# CHANGELOG for 1.1.x

This changelog references the relevant changes (bug and security fixes) done in 1.0 minor versions.

## v1.1.2

### Misc

 - [Improvement] Add unique index for each of three column instead one index on registry table
 - [Improvement] Remove useless index on section table

###Bundle

 - [Improvement] Update BundleLoader::loadFullBundles() to load every bundle services first

### ClassContent

 - [Improvement] Update ::commit() and ::revert() of ClassContentManager
 - [BUGFIX] Update ClassContentQueryBuilder::addClassFilter to ensure that requested classes are loaded
 - [Improvement] Update RevisionRepository::getAllDrafts to delete revision of unknown content
 - [Improvement] Improve FileJsonSerializeTrait::jsonSerialize()
 - [Improvement] [CONTENT SELECTOR] Hide content that not are not linked to pages

### Command

 - [BUGFIX] [users:update_rights] An existing group was looked for by its description

### Config

 - [BUGFIX] Fix Configurator::doBundleConfigExtend() to apply registry override only if save_in_registry exists and equals to true
 - [BUGFIX] Fix bundle configuration extend by file

### DI

 - [Improvement] Add project root directory into container's parameters

### Installer

 - [Improvement] Optimize EntityFinder::getNamespace()
 - [BUGFIX] Fix EntityFinder::getNamespace() to refer to ';' instead of '{'

### Page

 - [BUGFIX] Tree was empty after deleting a page with sub-pages
 - [BUGFIX] Fix delete page with section
 - [BUGFIX] Fix tree levels for Section movements

### REST

 - [Improvement] [LINK SELECTOR] Search engine and page numbering added
 - [Feature] Introduce Rest\RevisionController + Add ClassContentManager::revertToRevision() feature
 - [Improvement] KeywordController::getCollectionAction() accepts now uids parameter
 - [BUGFIX] Fix ClassContentController::patchDraftAction()
 - [Security] Activate ACL for bundles access and edit
 - [Secutiry] Add ACL checks on page soft delete
 - [BugFix] Correction of some Rest\Security annotations
 - [Improvment] [MediaController] Auto commit contents put or post in the library
 - [Feature] Introduce Rest\MediaFolderController
 - [Feature] Add a method to sort elements by uids preserve order

### Security

 - [BUGFIX] Fix authentication method to return a boolean. Fix this error : no authenticationprovider found for token of class usernamepasswordtoken
 - [Improvement] Leave try catch and add return false
 
### Util

 - [Improvement] Update the way EntityManagerCreator set Doctrine metadata cache

## v1.1.1

### Misc

 - [Improvement] Add registered method to handle fatal errors in BackBee application

### Bundle

 - [Improvement] Update BundleLoader::buildBundleDefinition() to also call ::started()
 - [Improvement] Make MetaDataResolver::resolveConst() protected to allow proper overload by bundles

### ClassContent

 - [Improvement] Add width and height to the uploaded file infos
 - [BUGFIX] Update RevisionRepository::getAllDrafts() to handle corrupted drafts
 - [Improvement] Ensure node_uid is set while created a new content in a main zone of a page
 - [BUGFIX] Fix IndexationRepository::getNodeUids() to avoid a 'Array to string conversion' notice

### Command

 - [BUGFIX] Fix CleanOrphan command to enable short classnames support

### Controller

 - [Improvement] Update FrontController to return response if exists after dispatch event

### Rendering

 - [BUGFIX] Fix Twig exception catching
 - [Resources] Remove and rename classcontent element phtml templates

### REST

 - [Improvement] Update MediaController::getCollection() to allow find by content uid
 - [BUGFIX] Fix MediaController::getCollection() to pass start and count arguments
 - [Improvement] Refactor MediaRepository and Rest/Controller/MediaController

### Tests

 - [Improvement] Allow to define a specific vendor dir in MockBBApplication

 
## v1.1.0

### Technical debt

 - [Command] Removed array_columun function - not suported by PHP 5.4
 - [Command] Fix critical errors from Insight
 - [Command] bbapp:create_site: code compatibility improvement

### ClassContent

 - [BugFix] Fix accept parameters creation for contentset

### Event

 - [BUGFIX] Ensure PostResponseEvent is triggered with a Response

### Page

 - [Improvement] Restoring page from trash

### Rendering

 - [Improvement] Removed use of datacontent helper which does not exist anymore

###Security

 - [Improvement] update services to remove duplicated role voter

## v1.1.0-beta3

### Command

- [Improvement] 5c5fda12c65ba78f468400f376c60b3bd77877ef - Add --drop option to bbapp:update command and introduce bbapp:create_site, bbapp:create_sudoer, users:update_rights commands

### Config

- [Improvement] ac5e5dc13d028149541792ccedd27f26a1357cd5 - Allow to define the overriding configuration persist level

### ClassContent

- [BugFix] 0176ba5deeeac2711fbc7c0faf979007d03c79f6 - Update ClassContentManager and ClassContentRepository to fix revision deletion on revert action
- [BugFix] 2bbbd58ba6c690244247080d965f06b76944ba52 - Fix AbstractContent has not main node attribute but AbstractClassContent has
- [Improvement] 0ed3abc89d51c1071595e0f6e461e818e03d085a - Add return $this to ContentSet::setParam() function
- [Improvement] 2004e2354f7437f664ca33cfb44ca6ede8c57d4b - Add main node state entry for json serialized content
- [Improvement] 3a13d1149c6dfcf18a6539880dff815db5337a2c - Merge options with accept on ContentSet to handle dynamic accepted subcontent
- [BugFix] 278c4fc9d3dbe364e32b3c6274a1d717e9cd36c7 - Fix getAccept() on old version layouts
- [BugFix] 805d1c4a5f4ddb1efc4c9f9b4565f328b5df129b - Test if content exists before removing it
- [Improvement] 36c3c6c514375422ffa0ca5fb56bc05e376a61e4 - Synchronize accept param with accept of ContentSet
- [Improvement] a6362e6540ebe7b5406f221947f69fbd685f74f4 - Add getDefaultOption method on AbstractClassContent
- [BugFix] b0a8a59732122b001fdff383b5ea4c1165f93c63 - Fix json encode on scalar element
- [Improvement] 3f8f70cf96713744dcddce11e2d24d58fdd97616 - Enable user to drop blocks only in zones that can handle the right render mode
- [BugFix] 3c6a8b029b06caf364d997e056b57052590a4f3c - Some special characters truncate the serialized data in db
- [Improvement] 7d75d18888b8c0a997ba69c5f7d8537972aa2e32 - Add MySQL index for OptContentByModified and improved ClassContentRepository pagination
- [Improvement] 410bba31c6b3bdf0ec9ec9b901786cb375c33d49 - Enable to search content with the first part of the uid
- [Improvement] fc415799e6db08f5d589bdef2d2ee5d7723d049c - Add test cases for Iconizer

### Event

- [BugFix] af404d2bfa43b33f9cd9c8cc9ca08264ce85f5bf - Fix major behavior bug in Dispatcher::triggerEvent()

### Page

- [Improvement] 4ce48ccdf1fe9483e6e83fe7ba1c8b3f7401ac06 - Add title field in SEO form by default
- [BugFix] 795c549824e0c9fadb0de7ba44ce9d48d4f21c4a - Fix page removing
- [BugFix] 181c131028dfdc3b551fcb91b9acb869558928fc - Fix PageRepository::toTrash() method
- [Improvement] ee6b3f390d2218040509fd97d3001f4236af073e - Redefine and develop search on pages

### Media

- [Improvement] f4ddb193d268994b1d9a3d5b6226cce7083a4ad2 - Allow to find if a media is linked to a content

### API

- [Improvement] 1015cf046e3da3bccf695118659713fd6e8d9abf - Set cache on bundle list
- [Improvement] 66492ddd278ae21100abdf4caa1d67b353a453cf - Add classcontent categories to cache
- [Improvement] ffc073e2b9b8cedcb4ba6a4e634de4d17497b6c0 - Page tree adjustments
- [Improvement] f18bf875b371051350d0805cda4d9657a30789b8 - Optimize definitions call

### Renderer

- [BugFix] db4c116a852c1824b943c2ab9dfed89d08b34ab3 - Fix metadata helper cause it throws warning on some use cases
- [BugFix] 0a0c91c94ddb7721c5d4bd4958d9663a82b18cc0 - Fix metadata helper use statement conflict
- [Improvement] d46269a9d96bc3563370f00b593387248c27634f - Allow the possibility to not compute empty metadata

### Rewriting

- [BugFix] a098ac41cdc5f6ddb9fb95e2360b621ffa1635e4 - Fix URL rewriting on first content vaidation + perf optimization

### Util

- [BugFix] fa8cd48c480f16614b064ab12db7a9c2260db9b8 - Avoid Doctrine cache and force reading from database

## v1.1.0-beta2

### Global

- [BugFix] 4cfe36fde447c302ffcc40e399760a03952fd578 - #637 - Fix executeQuery() and executeUpdate() calls

### BBApplication

- [Improvement] 08e166c8e0f287a4127a3fa2d410d4d1186d54f3 - improved init of bundles in BBApplication when debug is setted to false
- [BugFix] 53d58e605cfdf1c8b76fecdb2d70d183191822cc - [SwiftMailer] fix encryption if not set in mailer.yml config file
- [Improvement] a8ccfb90b809ab41bd84fe948f33f974e361c016 - Add security parameter to new SmtpTransport instance
- [BugFix] 0cd235187debdf8cb18e2b7193bae334a1490068 - Fix BBApplication::getEntityManager() when no database is available
- [BugFix] 0857bb16612e0659b157f968166a1241ac0fcf64 - Fix Resource dir

### Bundle

- [Feature] bee46d144146a7f2d4caf2cc26b9f69a596115f1 - Define BundleLoader as dumpable service


### ClassContent

- [BugFix] ba8911b30af874580bafeeee1770f1d439ee3f38 - fixing pagination on medialibrary
- [Feature] 56392624dd2147886b93e855dfc9220c009ed8d7 - add new has_elements property
- [Feature] ab2c6129aa5dd25beaae0a58471cb2f88a712169 - enabling search by uid
- [BugFix] ca2bcf6cfbd4b5abea84ec9ae185ca957da2733c - Fix retrieve of data when an element is null
- [BugFix] 799daaf91cc7080b06d6d77f506154f1f7c91654 - updated AbstractClassContent::defineParam() to allow vertical override of content default parameters
- [Improvement] 2efd4df6c4642fa556ea237183fb9d32338b40ee - updated ::getAction() in REST controller to support dynamic page as parameter
- [BugFix] 2973d6ff662deca1318196f18fad78422a19fcca - Fix IndexationRepository::getParentContentUids() for element contents
- [Improvement] 13a591a21b0bb781a6d476e5d6be938f42910a2f - improved draft revert process
- [BugFix] bd70f42735fce15fb22da17050f64994b5efa592 - Fix if draft dont have content
- [Feature] 17f80942e6c4d41c897c2a4905b8fa44fc9e9d8b - Add extra for add special config in elements part of yml
- [BugFix] 87b402869bb0226b3f9055f46e725b7b68c3c003 - Fix classcontent getcollection call
- [Improvement] b6f9667ad9c534f99949ff5638ae05b0d6af6226 - Allow to add multiple uid as criteria for classcontent::getCollection
- d55247fbbd7213d3c74f6133fa8efdf3c39d02e7 - ClassContent directory should not be required in context
- [BugFix] 03e5b9a7abffc9b4f7fc11c7398bb3197b946a15 - Fix stat for image upload
- [BugFix]  eef64ef9a0ebb772d65f9d143e6d619e55f8c985 - updated and fixed revert and commit of contents drafts
- [Improvement] d969bdd16c64c6a73b45453e692f916bfef3ca65 - Disabling cache in ClassContentManager::getAllClassContentClassnames when debug mode is enabled
- [BugFix] 2b131aa7e0e4d2dcee6cdcb4837272e2c2e9f3cc - removed cast to array cause it generates issue in some cases
- [BugFix] 3a927b6da889150a58c5c5196fef42fc0b76ef82 - updated ClassContentController::postAction to also handle elements and parameters data

### Config

- [BugFix] 801a4408bc30dd8844690264067d81c03ad65e88 - Fix override of config to merge several config instead of replace
- [Improvement] 98ddcc8b2fbfa06b68cfe9e792507cef75f95631 - remove hidden services
- [BugFix] 1edd96d2db1a01da70b126b91101e4bbe3fd9026 - Fix default return of method Configurator:getConfigDefaultSections()

### EntityFinder

- [BugFix] 7ca60cd4b97194b2a3ef8015caced502ed881032 - Fix namespace resolution of a php class file

### Keyword

- [Improvement] b4cfa054525eb707752eaf8b5383f2b1a5363165 - updated Element\KeywordRepository::updateKeywordLinks() to put third argument optional
- [Improvement] 9dedafd56a1c992824c03114c09f22fa767b3110 - Update Keyword to Content joins
- [Feature] 26fd1e225641aa4c6a7a408f44761b84f8bafee3 - adding paginator to kw
- [BugFix] 0ed9335d8443ac7e8e81b3c1c89172effe5799c1 - fixing Rest
- [BugFix] cbae9abdcb3f423c68e2d72be6bd6e1324ba0326 - fixing kw

### Metadata

- [Feature] d3ca6b78a8a066f4da230465be3cb3ba198b28ca - Refactoring metadata computing

### Page

- [BugFix] 0d455910d660f533d777f84150883710722e3eb7 - Remove test on past publishing of page
- [Improvement] 3487b20e165b8f839dc522c0779d41e093d58690 - Set to false on Doctrine\ORM\Tools\Pagination\Paginator construct to preserve results order
- [BugFix] 20bccc7c41fafde97958a208419386f3f4d4c117 - fix clone action
- [Improvement] bac0994b7319396870546ed1f5ee69fbdc1a3c65 - Add right for page deletion
- [BugFix] e151ab91834ca8b9d397b401027641de2400e38d - fixing pageController
- [Feature] 6bdf49b780efbb8afc93e00ee094dc27af1675d2 - Add final layout management

### Renderer

- [BugFix] 15dc68d211b34d252055a7b35bf6fd10437256bb - added TwigListener::onApplicationReady() to load twig extensions
- [Improvement] 42d1c0290b845122d8702010d5dd1bd5ed279466 - improved Adapter\Twig and removed 'bbapplication.start' listener
- [BugFix] 96774f21a50a91cfea8019194e8caed3460ac8d8 - preserve FrontControllerException in TwigAdapter

### Rest

- [Improvement] 9e4a7e0e1c5f4d4cf5a68fa266621d1ec4852844 - Add JMS metadata cache adapter allowing to store it by BackBee cache services (bootstrap by default)
- [BugFix] 0a5f26f27aca65de99959d8a288d8bf5a1aa8462 - Fix API response to ClassContentController::getDraftCollection()
- [BugFix] cfbf52539e6107f2529e704f022eda92ff485c76 - fixing pagination
- [BugFix] 2fecc7e5c10d00f76a2c9b4a7f41e4c4b53e44b9 - fixing pagination listener
- [BugFix] 464f6b3156f4a05cada3c7345019f2b64f8823e0 - Catch RuntimeExcepion for mailer not available

### Routing

- [Improvement] 619cb6b595f64d685921ca93d331740ca356160b - Set some RouteCollection properties protected to allow extends
- [BugFix] 625d497d40fdafb76671d1eb72b5715e46f3071b - add capitalized char to routes

### Security

- [Feature] 2e8d18bbbfb7c7dbae386bd383c6fa586ceafb41 - Added ``security.interactive_login`` event

### Session

- [Improvement] 630d34c715bd72b73de19b7bd301fe3eb0ffb463 - Updated the PdoSessionHandler configuration
- [Improvement] df92989cec1467b4e4473ded1f30a0a4fbe8d1e5 - use factories instead of configurators
- [Improvement] 0f3c9f4b9a9a044f3c0f7757aa7a50684b3a5e63 - improved session configurator
- [Improvement] 0ba8d1ec88dc418d54b8bb4ae9040a9f95ea7e84 - Make the session configuration optional
- [Feature] 5b5167b1b32dfcd1c0325e00a956fc09eed76a0a - Allowed to set a cookie_domain session

### Site

- [BugFix] e69374c65f1395bcd04c036d8afc6e70559945ae - add missing annotation and remove excess annotation

### Util

- [Improvement] 4c7836946d49d931893d5cbf93da2789e1793e63 - Add debug in Buffer
- [Feature] 2010289f7f39b96e9f49a66ada1cbc2c1da976e2 - [Doctrine] Allow to define several db hosts in order to select one randomly on connection


## v1.1-beta1

* [Feature] 8063d84dbe51f6644faae4831d27727f3a3db008 - Improved Cache architecture
* [Feature] b0c5e463ced3cb507b9f1aed7b485f084e63b7c4 - Improved Configuration panel
* [Feature] fa51bc13e13c5005f139d6e01113c5aa1d46ecfa - Added REST Site getCollection action
* [Feature] 2a114f223d0e8be689a8058e1d64336382d5c85d - Created a REST v2 for getCollectionAction from PageController (Added root query filter)
* [Feature] ebe4ed1e8cd91b5c4e86d539bdd984d537d123aa - Stored short content classnames in Db
* [Feature] f1b79cb09a186367ebf8f6ef46a3db2d640e05b8 - Added new `keyword` REST service
* [Feature] baf7f442b73194546322285d22b48c693e4cf010 - Added `classcontent.postload` event to be able to priorize listeners & introduced  ``MalformedParameterException`` to prevent parameters without value entry
* [Feature] 24a8d0cdef35726ceb852f140732a1fab2790f4f - Introduced REST API to allow batch actions on Page entity
* [Feature] de349111a9ed94974d1f5a45397137fdcb5a9ee3 - Optimized entity UIDS
* [Feature] 34c9b50f2fd927b3a6e6d25653d62a3e96c99b1f - Added new Page ancestors action (REST)
* [Feature] ac387ecc74c9b2799851aaf70be97ee44f5cda64 - Introduced ClassContent Iconizer
* [Feature] cb721138c07f9cd4dc82a096bfbfade0ceab378a - Re-introduced `getResourcesDirectory()` in AbstractBundle
* [Feature] 14637a74547c54b5e82fd67d367efc6926bce038 - `Added KeywordController::getAction()` (REST)
* [Feature] 50a31ce53b7d21e31027be5de0dfd3221b312ce9 - test empty value before to return

* [BugFix] dbbf8bd6b34e20197dabc2a7c59623f73fcd50b8 - Fixed Controller event name
* [BugFix] 8d7edffe3ad0ff3c5ee23f1c01016d63747e5004 - Re-introduced  `getBundle()` to AbstractBundleController
* [BugFix] 10ff9da47031bc67a18837769e6d050e38f1993b - Fixed generation of url with special characters like '+'
* [BugFix] c65d7fbf0b414aaf711c848bfcea24f1c0100b78 - Improved error rendering message
* [BugFix] 7af24ece6dc070b4038fa2074266d81aff1ebf26 - Fixed exception listener
* [BugFix] eb643020f8d31ba50eeb8c95462621883c3fe354 - Used `mb_strotolower` to ensure UTF-8 encoding on windows platform
* [BugFix] 42e95c3026e16012f013f8dccee3945cf6fa7422 - Added Symfony Form component as a suggested dependency
* [BugFix] c0ca4405110361c4b460549e0439fd81c2a5ee06 - Fixed case for rest.controller.classcontentcontroller.getaction.postcall in events.yml
* [BugFix] 691c294ba142e3daffae2f558a5605120f59ef53 - Introduced `AbstractContent::throwExceptionOnUnknownClassname()` to handle unknown class contents
* [BugFix] 0164561b67bccdfe32a46ea8b2396134e80dd050 - Updated GroupController::getCollectionAction()
* [BugFix] 3f7985e7b9577b94ab9f41a705ff960a63e390ec - Fixed update `bbapp:update` command
* [BugFix] b126a97b74e4bc823d53d393a055ddeb8e291933 & 6b29c2321b0d12d1f61c94cc71e74ad5897820d1 - Tried to use request when available
* [BugFix] 3c517a8152d817003dd5253fce58a5ae9466cde5 - Reviewed Page Rest actions
* [BugFix] c2fd6336b0bcdb50e7bfb25773f0d7aacabc326e - Fixed `ClassContentRepository::getLastByMainnode()`
* [BugFix] 2343d3cbcff6e0b8462e8fa788721312ac924909 - Fixed PageBuilder, don't persist new root when no persist method provided
* [BugFix] fa3a839b9324f7a26303f37b9a4bfcfb3384c18c - Fixed `postload` event
* [BugFix] 4ce06f5a92de8143961d7d7dd02c5e59bb14acfa - Fixed Revision `getMode()`
* [BugFix] b258418bc7c3993f96ad405482cebb48db89d00d - Fixed Page getCollection action (REST)
* [BugFix] cb3f1a62ab76b1ac034c89ea5abd24b6c7a9fbd0 - Updated ClassContentManager::prepareElements() to handle array of values of an element
* [BugFix] 127d1eb5968d421b8bb4324d055589d400cef114 - Fixed moving on non-section Page
* [BugFix] cf044169a7c4eb92d694c7eaab466391f8eae8aa - Updated  `AbstractContent::computeElementsToJson()` to always return elements key whatever its values
* [BugFix] bb6b9b2b2ea67a04db825a2c66a5a14984ffc611 - Workaround to allow Symfony debug component to return valid HTTP code errors
* [BugFix] 2ce334ed7ef992e101d41bdba990a5bc70da7370 - On content commit we force autoload of classcontent classes
* [BugFix] 33409cb31d79df66bbbdc02fde73002a0c07eb58 - Fixed bug in objectKeyword that does not a parameter

* [TechnicalDebt] d2fff3b0e32450b939f83f542d8e2cc606c8e660 - Updated every bundle command
* [TechnicalDebt] 852753af18fccf18cb8dd09540b435697fecdd0b & 29d0e84c89ace9c5fd47b790b86b252531885ba9 - Removed unused use statements and variables
* [TechnicalDebt] 30a5c533a106b1495fb34667d13b711b356eca8e - Reduced technical debt
* [TechnicalDebt] 9271b3fadfd2a9db022763376ffb0848e1dac58c - Removed useless function call