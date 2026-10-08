# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Electronic Voting System (電子投票系統；原院士選舉參考實作) — MIT licensed. Yii2 platform supporting two voting types:
- **表決投票** (TYPE_NO_AUTH='0'): No authentication required
- **匿名投票** (TYPE_ANON='1'): Password-based anonymous voting

**Note**: The named voting type (記名投票, TYPE_VOTER='2') has been removed as of 2026-01-02.

## Development Commands

### Testing

**Unit Tests:**
```bash
php vendor/bin/codecept run unit
```

**Acceptance Tests:**
```bash
# Step 1: Start the test server (uses router-test.php to route all requests to index-test.php)
php -S localhost:8080 -t web web/router-test.php

# Step 2: Load fixtures (in a separate terminal)
# IMPORTANT: Use --appconfig to target test database
# IMPORTANT: Must include Config,Users,Rbac or acceptance test logins will return 403 Forbidden
echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac" --appconfig=config/console-test.php

# Step 3: Run acceptance tests
php vendor/bin/codecept run acceptance

# Run specific test class
php vendor/bin/codecept run acceptance AnonVoteCest

# Run with debug output
php vendor/bin/codecept run acceptance --debug

# Step 4: Clean up after testing
echo "yes" | php yii fixture/unload "Ballots,BallotsSelected" --appconfig=config/console-test.php
```

**Test Server Notes:**
- The `router-test.php` script routes all requests through `index-test.php` which uses the test configuration
- Test configuration connects to the `voting_test` database (defined in `.env` as `TEST_DB_*`)
- Failed test outputs are saved to `tests/_output/` directory

**Fixture Management:**
```bash
# IMPORTANT: Always use --appconfig=config/console-test.php to ensure test database is used

# Load all fixtures
php yii fixture/load "*" --appconfig=config/console-test.php

# Load specific fixtures
php yii fixture/load "Votes,Parties" --appconfig=config/console-test.php

# Generate test data from templates
php yii fixture/generate <fixtures> --count=<number> --appconfig=config/console-test.php

# Unload all fixtures
php yii fixture/unload "*" --appconfig=config/console-test.php

# Unload specific fixtures
php yii fixture/unload "Ballots,BallotsSelected" --appconfig=config/console-test.php
```

**⚠️ Important Note:**
- **Without `--appconfig=config/console-test.php`**: Commands use `config/console.php` and target the **production database** (`voting`)
- **With `--appconfig=config/console-test.php`**: Commands use `config/console-test.php` and target the **test database** (`voting_test`)
- **Always use the test config** when running fixtures for testing to avoid affecting production data!

### Console Commands

Run console commands via the `yii` script:
```bash
php yii <controller/action>
```

## Architecture Overview

### Dual Authentication System

This system implements two separate identity components for different authentication needs:

1. **user component** (`/components/AdminIdentity.php`): Admin local password authentication
   - **Authentication**: Local database account/password (via `AuthController::actionLogin()`)
   - **Roles**: sa (system admin), va (vote admin), ga (group admin), gm (group member)
   - **Session timeout**: 7200s
   - **Accessed via**: `Yii::$app->user`
   - **Features**: RBAC role assignment via `afterLogin()` method, password validation, account lockout after failed attempts

2. **anon component** (`/components/Anon.php`): Anonymous password-based authentication
   - **For**: 匿名投票 (TYPE_ANON='1')
   - **Accessed via**: `Yii::$app->anon`
   - **Uses**: Logins table to prevent duplicate sessions

Both extend `/models/UserIdentity.php` which implements custom session-based identity storage.

### Core Data Model Relationships

```
Votes (投票場次)
├── Round (輪次) - Multi-round voting support
│   └── Questions (問題) - Per round/party
│       ├── CandiConfig (候選人配置)
│       ├── CandiData (候選人) - Multiple generation modes
│       └── QuestionsGroupRule (特殊規則)
├── Parties (分組)
├── Passwords (密碼) - For anonymous voting
├── Ballots (選票)
│   └── BallotsSelected (圈選記錄) - Supports ranked voting via 'rank' field
└── Results (開票結果)
```

**Key Model Behaviors:**
- `UniversalActiveRecord` (`/models/UniversalActiveRecord.php`): Dynamic ActiveRecord base class for flexible table operations
- All major models have bilingual fields (*E suffix for English)
- Models use relationships heavily - check model files for `relations()` or `get*()` methods

### Voting Workflow

1. **Session Creation** (`ManageController`): Create vote with type, rounds, parties
2. **Round Configuration** (`RoundController`): Configure multiple voting rounds
3. **Question Setup** (`QuestionController`): Define questions per round/party with voting rules
4. **Candidate Configuration** (`CandiController`):
   - Unified or per-question configuration
   - Generation modes: manual, conditional, file import, round import
5. **Password Generation** (`PasswdController`): For anonymous voting (TYPE_ANON='1')
6. **Voting** (`VoteController`):
   - Authentication based on vote type
   - Ballot creation with validation via QuestionsGroupRule
   - Supports ranked voting (序位投票)
7. **Counting** (`CountController`): Tallying, results calculation, export
8. **Results Display** (`ResultController`): Public results based on ResultsConfig

### Configuration System

**Environment-based Configuration:**
- Custom config loader in `/config/Config.php` + `ConfigLoader.php`
- Supports environments: product, test, alpha, ws
- Encrypted sensitive fields using `ConfigManager` + `MasterKeyLoader` (master key derived sub-keys)
- Main configs: `web.php` (application), `params.php` (parameters), `console.php` (CLI)

**Code Tables in params.php:**
```php
'ct.voteType' => ['0' => '表決(無須驗證)', '1' => '匿名(亂數密碼)']
'ct.voteProcessAry' => [0-7 statuses including 補登投票]
'ct.activeAry' => ['0' => '等待', '1' => '進行', '2' => '截止', '3' => '補登']
```

Access via `Yii::$app->params['ct.voteType']`

### Custom Base Classes

When creating new controllers or models, extend these:

- **Controllers**: Extend `/components/Controller.php` (not `yii\web\Controller`)
  - Provides `NotAllowedAccess()` for access control errors
  - Custom denyCallback factory for RBAC

- **Models**: Extend `/components/Model.php` (not `yii\base\Model`)
  - Provides `i18n()` helper for bilingual field display
  - `createMultiple()` for batch model creation
  - `trimParams()` for filtering empty query params

- **ActiveRecord**: Extend `/models/UniversalActiveRecord.php` for dynamic table handling

### Access Control (RBAC)

Permission-based system with custom rules in `/rules/`:
- `voteManagRule`: Vote management permission check
- `ballotWorkRule`: Ballot operations permission check
- `groupManagOwnVoteRule`: Group's own vote management
- `groupManagByMemberRule`: Group management by member
- `groupViewOwnRule`: View own group permission
- Role definitions in `config/params.php` under 'ct.apRoles': sa, va, ga, gm

Check permissions in controllers using `behaviors()` with AccessControl filter or explicit `Yii::$app->user->can('permission')` checks.

### Frontend Architecture

**Asset System:**
- AdminLTE 3.1.0 admin template
- Bootstrap 5 framework
- Custom asset bundles in `/assets/`
- Frontend libraries in `/frontend/` (see `frontend/README.md`). Dialogs: kartik-v/yii2-dialog and Bootstrap 5 Modal.

**Custom Widgets** (`/widgets/`):
- Extend Yii widgets for common UI patterns
- CardStyle, DynamicTabbed, BootstrapSelect, UrlModal, etc.

**Layouts:**
- Main layout: `/views/layouts/main.php`
- Voting-specific layouts in offcanvas/ and basic/
- Theme support via pathMap configuration

### Database Schema Notes

**Composite Primary Keys:**
Many tables use composite keys:
- `ballots`: voteID + party + ballotID
- `ballotsSelected`: voteID + ballotID + selCandiID
- `questions`: questionID (auto-increment) with voteID + round + party uniqueness
- `passwords`: voteID + passwd

**Important Fields:**
- `active`: Vote status (0=等待, 1=進行, 2=截止, 3=補登)
- `type`: Vote type (0=表決, 1=匿名；記名 type=2 已移除)
- `round`: Current/specific round number
- `isValiable`: Ballot validity flag
- `rank`: For ranked voting in BallotsSelected
- `genMode`: Candidate generation mode in CandiData

### Testing Structure

- **Unit tests** (`tests/unit/`): Test individual models and components
- **Functional tests** (`tests/functional/`): Controller action testing without browser
- **Acceptance tests** (`tests/acceptance/`): Full workflow testing with browser simulation
- **Fixtures** (`tests/fixtures/`): Test data in `/data/` subdirectory

Always load required fixtures before acceptance tests and clean up after.

### Internationalization

- Language component: `/models/Language.php`
- Message files in `/messages/app/`, `/messages/vote/`
- Bilingual support: zh-TW (default), en-US
- Use `Yii::t('app', 'message')` for translation
- Models have *E fields for English versions of text fields

### Security Features

- **Anonymous Voting Protection**:
  - Password status tracking (啟用/未啟用, voted flag)
  - Session isolation via Logins table
  - No linkage between password and ballot after voting
  - Dual-track support (dtrack) for paper+online voting

- **Authentication**:
  - Cookie security: httpOnly, secure, SameSite=Strict
  - CSRF protection (configurable)
  - Host control filter

- **Data Protection**:
  - Encrypted configuration fields
  - Session-based identity storage (not cookie-based)
  - Comprehensive operation logging (100+ types in Logs table)

### Common Patterns

**Creating Ballots:**
```php
// Ballot creation is transactional
// See BallotController::actionCreate() for full flow
// Always validate against QuestionsGroupRule
// Store in Ballots + BallotsSelected tables
```

**Checking Vote Status:**
```php
// Use ct.activeAry code table
$active = $vote->active; // 0=等待, 1=進行, 2=截止, 3=補登
```

**Handling Multiple Rounds:**
```php
// Round is composite with voteID
// Check Vote->round for current round
// Questions, Candidates are round-specific
```

**Dynamic Table Operations:**
```php
// Use UniversalActiveRecord for flexible queries
// Supports dynamic attributes and table operations
```

### File Locations Reference

- **Controllers**: `/controllers/` - Naming: `*Controller.php`
- **Models**: `/models/` - 50+ model files
- **Views**: `/views/<controller>/` - View files per controller
- **Components**: `/components/` - Custom Yii components
- **Widgets**: `/widgets/` - Reusable UI components
- **Actions**: `/actions/` - Standalone action classes
- **Rules**: `/rules/` - RBAC rule classes
- **Traits**: `/traits/` - Reusable traits
- **Interfaces**: `/interfaces/` - Interface definitions
- **Tests**: `/tests/` - Test suites and fixtures

### Logging

Operation logging via `Logs` model with LogInterface constants:
- 100-199: 投票管理 (Vote management - create, edit, delete, parties, voters, files, candidates, passwords, ballots, counting, results)
- 200-299: 問題管理 (Questions - create, edit, delete, import)
- 300-399: 群組管理 (Groups - create, edit, members)
- 400-499: 系統設定與使用者 (Config, Users - create, edit, delete, admin login)
- 500-599: 投票者登入 (Voter login - anonymous password, named voter)
- 600-699: 輪次管理 (Round - create, edit, switch)
- 700-799: 問題特殊規則 (Questions group rules - create, update)
- 800+: 樣板管理 (Templates - create, update, delete)

Use `Logs::log()` to record operations.
