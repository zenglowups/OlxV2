# OLXv2 — FINAL PRODUCTION BUILD INSTRUCTION

Repository:

`https://github.com/zenglowups/OlxV2.git`

Default branch:

`main`

The repository currently starts essentially empty.

You are the principal senior software engineer responsible for building this product from zero into a production-quality Romanian classifieds marketplace.

The internal development codename is:

**OLXv2**

This is only a temporary internal project name.

Do not build branding that depends permanently on the OLX trademark.

The application must be easy to rename before public production launch.

---

# CORE PRODUCT PROMISE

The platform's product promise is:

**"The easiest and safest place in Romania to publish something for sale."**

This is a REAL full-stack application.

It is NOT:

* a mockup;
* a static demo;
* a Figma recreation;
* a frontend-only prototype;
* a collection of fake buttons;
* a visual proof of concept.

Build real working functionality.

A page is not complete merely because it looks correct.

A feature is complete only when its:

* backend works;
* database persistence works;
* authorization works;
* validation works;
* responsive interface works;
* error states work;
* automated tests pass;
* important real browser flow has been verified where browser tooling is available.

Never present mocked functionality as completed functionality.

---

# DESIGN REFERENCES

The repository may contain visual references under:

`docs/design-references/`

Inspect these images before designing the main interface.

They communicate the desired:

* visual hierarchy;
* spacing;
* premium feeling;
* trustworthy feeling;
* color direction;
* marketplace card structure;
* mobile-first philosophy;
* navigation concepts.

They are REFERENCES, not pixel-perfect specifications.

Do NOT waste engineering time reproducing these screenshots exactly.

Do NOT turn them into static mockups.

Build real components backed by real application data.

If a visual concept conflicts with usability, accessibility, security or real product functionality, prioritize the functional product.

The preferred visual feeling is:

* premium;
* trustworthy;
* clean;
* airy;
* modern;
* calm;
* highly readable;
* friendly;
* professional.

Suggested visual direction:

* neutral/off-white surfaces;
* deep navy/slate text;
* restrained teal/emerald primary accent;
* small warm gold accents for premium/trust elements;
* excellent contrast;
* large readable typography;
* generous spacing;
* restrained shadows;
* rounded but professional cards;
* minimal clutter.

Create an original identity.

Do not reproduce OLX branding.

---

# PRODUCT PRINCIPLE

Every important product decision should answer:

**Does this help a buyer find the correct seller faster, or help a seller receive qualified buyers more easily?**

If not, it is probably not a V1 priority.

---

# TARGET MARKET

Initial market:

**Romania**

Architecture must allow later European expansion.

Initial languages:

* Romanian — primary;
* English.

Use proper Laravel localization.

Do not hard-code application text throughout templates.

---

# TECHNOLOGY STACK

Build a modular Laravel monolith.

Use:

## Backend

* PHP
* Laravel
* PostgreSQL
* Redis

## Frontend

* Laravel Blade
* semantic HTML5
* CSS
* Alpine.js where appropriate
* lightweight vanilla JavaScript where appropriate
* Vite for frontend assets

Do NOT introduce React, Next.js, Vue, Nuxt or a separate SPA unless a specific later requirement genuinely justifies it.

Do NOT build microservices.

Do NOT use Kubernetes.

Do NOT introduce unnecessary infrastructure complexity.

The application should remain understandable and maintainable by a small engineering team.

---

# INFRASTRUCTURE PORTABILITY

Production architecture must remain compatible with ordinary professional Linux VPS providers in Romania or elsewhere.

Target compatibility:

* Ubuntu LTS
* Nginx
* PHP-FPM
* PostgreSQL
* Redis
* Laravel queue workers
* Laravel Scheduler
* Supervisor/systemd or equivalent process management
* S3-compatible object storage
* Cloudflare
* Git-based deployments

Do not make the application dependent on one proprietary hosting provider.

The application should be easy to migrate between VPS providers.

---

# LOCAL DEVELOPMENT — CRITICAL REQUIREMENT

The project owner is not expected to manually manage PHP, PostgreSQL, Redis, queues and other infrastructure every time the application is started.

Create a reproducible Docker-based local development environment.

A normal development session should eventually be startable with approximately:

`docker compose up -d`

The environment should coordinate:

* Laravel / PHP;
* Nginx or appropriate local web server;
* PostgreSQL;
* Redis;
* Laravel queue worker;
* Laravel Scheduler;
* frontend/Vite tooling;
* Mailpit for local email testing;
* S3-compatible local object storage such as MinIO where appropriate.

Persist application development data using Docker named volumes.

DO NOT silently delete development databases.

DO NOT destroy volumes as part of normal start/stop commands.

Provide convenient Windows scripts:

`START-OLXv2.bat`

`STOP-OLXv2.bat`

Where practical, START should:

1. verify Docker availability;
2. start required containers;
3. wait for required services;
4. run only safe startup operations;
5. clearly print the application URL.

The developer should then be able to open:

`http://localhost`

or another clearly documented local URL.

Document the exact URL.

Also document Mailpit's URL.

The project must remain easy to inspect using Google Chrome throughout development.

---

# SOURCE CONTROL

Use Git from the beginning.

Create logical commits.

Do not create huge meaningless commits.

Never commit:

* API keys;
* passwords;
* production tokens;
* database passwords intended to remain secret;
* OAuth client secrets;
* Cloudflare secrets;
* payment credentials.

Maintain `.gitignore`.

Because this repository may be public, treat secrets extremely carefully.

Use `.env.example` for configuration examples.

Never commit `.env`.

---

# PROJECT DOCUMENTATION

Create and maintain:

`README.md`

`AGENTS.md`

`IMPLEMENTATION_PLAN.md`

`docs/architecture.md`

`docs/deployment.md`

`docs/testing.md`

AGENTS.md should contain the important permanent engineering constraints for future Codex turns.

IMPLEMENTATION_PLAN.md is the project's execution state.

It must contain:

* milestones;
* completed work;
* current work;
* next work;
* known issues;
* architectural decisions;
* pending third-party configuration;
* test status;
* performance work still required.

The repository is the source of truth.

Do not depend on chat memory to remember project state.

---

# MOBILE FIRST

Mobile usability is fundamental to business success.

Design mobile first.

Every primary workflow must work correctly on:

* small smartphones;
* large smartphones;
* tablets;
* laptops;
* desktop monitors.

Do not merely shrink desktop UI.

Mobile layouts must be intentionally designed.

Important interactive controls should generally be around 44px or larger.

Use obvious text labels for important actions.

Avoid interfaces dependent on tiny icons.

---

# ACCESSIBILITY

Aim for WCAG 2.2 AA principles.

Implement:

* semantic HTML;
* keyboard navigation;
* visible focus states;
* adequate text contrast;
* readable typography;
* accessible labels;
* logical headings;
* understandable validation errors;
* appropriate ARIA only where necessary;
* large touch targets.

Do not communicate important states using color alone.

---

# USER TYPES

Support:

1. Individual seller
2. Business seller
3. Verified business

Business verification is an administrator-controlled process.

Potential verification information includes:

* legal company name;
* CUI/CIF;
* ONRC-related proof;
* ANAF-related proof where appropriate;
* supporting business documents.

Never automatically display a "verified by ANAF/ONRC" statement merely because a user typed a number.

Verification status must be based on an actual defined verification workflow.

Securely protect uploaded legal documents.

Do not expose them publicly.

---

# AUTHENTICATION

Users MUST register before posting a listing.

Support:

* email + password;
* Google OAuth;
* Facebook OAuth.

If OAuth credentials are not currently available:

* implement the correct integration structure;
* use environment variables;
* document setup;
* do not invent credentials;
* do not create fake successful OAuth flows.

Email verification must be fully functional.

Phone authentication may be added later.

Use secure password hashing.

Implement:

* registration;
* login;
* logout;
* password reset;
* email verification;
* session management;
* account suspension;
* account banning.

Rate-limit sensitive endpoints.

---

# PUBLIC USER PROFILE

Support:

* username;
* profile picture;
* account age / member since;
* seller type;
* verification status;
* reputation;
* reviews;
* seller review replies;
* active advertisements;
* public seller description.

Do not expose unnecessary private information.

---

# BUSINESS PROFILE

Business profiles should remain simple and trustworthy.

Support:

* logo;
* legal/business name;
* public description;
* verified business badge where genuinely approved;
* CUI/CIF where appropriate;
* reputation;
* reviews;
* active listings;
* chosen contact method.

Do NOT create complicated custom storefront builders in V1.

---

# REPUTATION SYSTEM

Seller reputation is a central product feature.

Reviews contain:

* 1–5 stars;
* maximum 800-character text review.

Rules:

* reviewer must have a registered account;
* user cannot review themselves;
* seller may reply publicly;
* seller may report a review;
* administrator may investigate and remove abusive/fake reviews.

Default anti-abuse rule:

prefer allowing review creation only when the reviewer has had a meaningful marketplace interaction with the listing/seller, such as a recorded contact-detail reveal.

Make this rule configurable.

Prevent obvious duplicate review abuse.

Record metadata useful for detecting suspicious reputation manipulation.

---

# LOYALTY / TRUST BADGES

Support real data-driven badges such as:

* Email Verified
* Phone Verified — only when genuine verification exists
* Verified Business
* Member 1 Year
* Member 3 Years
* Long-Term Seller
* Trusted Seller

Badges must be explainable.

Never fabricate trust indicators.

---

# CATEGORY SYSTEM

Build a database-driven hierarchical category system.

Initial major categories:

* Cars
* Car Parts
* Real Estate
* Electronics
* Gardening
* Fishing
* Decorative Objects
* Clothes
* Sports

Support unlimited future nested categories.

Examples:

Electronics
→ Phones
→ Tablets
→ Computers
→ TVs
→ Audio

Cars
→ Cars
→ Motorcycles
→ Commercial Vehicles
→ Parts
→ Wheels/Tires

Do not hard-code every category into application logic.

---

# DYNAMIC CATEGORY ATTRIBUTES

Build an extensible category-specific attribute engine.

Example Car attributes:

* brand;
* model;
* manufacturing year;
* mileage;
* fuel;
* transmission;
* engine;
* condition.

Example Real Estate:

* property type;
* rooms;
* usable area;
* floor;
* construction year;
* heating;
* condition.

Example Electronics:

* brand;
* model;
* storage;
* condition;
* warranty.

Administrators must be able to manage the category/attribute system without rewriting controllers.

Do not implement every imaginable attribute before launch.

Build the mechanism correctly.

---

# POST-AN-AD EXPERIENCE

This is one of the product's strongest differentiators.

Use a very simple step-by-step wizard.

Concept:

1. Category
2. Photos
3. Title and description
4. Category-specific details
5. Price
6. Location
7. Contact
8. Preview
9. Publish

The experience should feel like answering simple questions.

Avoid enormous forms.

Use progressive disclosure.

Every step must:

* validate server-side;
* save progress;
* allow Back;
* allow Continue;
* show progress;
* preserve previously entered data.

---

# DRAFTS

Unfinished listings automatically save as drafts.

Drafts are private.

Only:

* the owner;
* authorized administrators where legitimately required

may access them.

Users should be able to close the site and resume later.

---

# PHOTOS

Maximum:

**8 images per listing.**

NO VIDEOS.

Do not implement video uploads.

Validate images server-side.

Supported common source formats may include:

* JPG/JPEG;
* PNG;
* WebP;
* HEIC only if server processing support is reliable.

Never trust:

* file extension;
* original filename;
* browser-provided MIME type alone.

Processing should:

* inspect real image type;
* enforce size limits;
* normalize orientation;
* generate safe random storage names;
* strip unnecessary metadata where appropriate;
* create optimized variants;
* create WebP and/or other efficient formats where appropriate;
* create thumbnails.

Suggested variants:

* thumbnail ~320px;
* listing card ~640px;
* listing/full ~1280px.

Do not send a seller's original 5–10 MB image to a search-results card.

Allow:

* upload;
* reorder;
* delete;
* select primary image.

---

# OBJECT STORAGE

Do NOT store listing images inside PostgreSQL.

Do NOT design production to keep millions of listing images on the application VPS filesystem.

Use Laravel filesystem abstraction.

Development:

* MinIO or compatible local object storage.

Production:

* Cloudflare R2 or another S3-compatible object store.

Application code should not need rewriting when switching development storage to production storage.

---

# LISTING DATA

Listings need:

* owner;
* category/subcategory;
* title;
* description;
* structured attributes;
* price;
* pricing mode;
* location;
* contact method;
* photos;
* creation date;
* expiration date;
* status;
* moderation state;
* statistics.

Pricing modes:

* fixed;
* negotiable;
* free;
* exchange;
* contact for price.

---

# LOCATION

Romania V1.

Support:

* country;
* county;
* locality/city.

Do not expose precise private home addresses by default.

Allow future European location expansion without schema redesign.

---

# LISTING STATES

At minimum:

* draft;
* active;
* reserved;
* sold;
* expired;
* suspended;
* removed.

Default active lifetime:

**30 days.**

Send email reminders:

* 3 days before expiration;
* on expiration day.

Support:

* edit;
* mark reserved;
* mark sold;
* renew/repost where allowed;
* remove.

---

# CONTACT SYSTEM

There is NO internal chat.

This requirement is final for V1.

Do NOT build:

* private messaging;
* WebSocket chat;
* attachments;
* unread message counters;
* message database;
* message moderation.

Seller selects one contact method:

* phone
  OR
* email.

Buyers may browse public listings without an account.

Buyer MUST register/login before revealing the seller's actual contact information.

Display buttons such as:

* "Afișează numărul de telefon"
* "Afișează adresa de email"

Do not place protected contact data directly in initial public page HTML.

Retrieve the actual contact value from a protected backend endpoint.

---

# CONTACT ANTI-SCRAPING

Contact reveal is sensitive.

Protect reveal endpoints with:

* authenticated user;
* authorization;
* server-side rate limiting;
* abuse monitoring;
* activity logging;
* Cloudflare Turnstile integration structure.

A legitimate authenticated user should not solve a human challenge unnecessarily on every single reveal.

Use sensible session-based human verification.

Record:

* buyer user ID;
* seller user ID;
* listing ID;
* contact method;
* timestamp;
* appropriate security metadata.

Do not expose excessive user information.

---

# FAVORITES

Registered users can:

* favorite;
* unfavorite;
* browse favorites.

Use real persistent data.

---

# RECENT SEARCHES

Logged-in users can:

* see recent searches;
* repeat a search;
* clear history.

---

# SAVED SEARCHES

Support saved searches containing:

* query;
* category;
* filters;
* price;
* location.

Architecture should allow future email notifications for new matching listings.

---

# SEARCH ENGINEERING

Initial search may use PostgreSQL.

Support:

* text query;
* category;
* subcategory;
* location;
* price range;
* category-specific filters;
* newest sorting;
* relevance-oriented sorting where practical.

Create correct indexes.

Avoid N+1 queries.

Use pagination.

Do NOT retrieve every matching listing into PHP and then paginate in memory.

Query only the required result window.

Use cursor/keyset pagination where beneficial for large datasets.

Build search behind a clean application-level abstraction, for example conceptually:

`ListingSearchService`

so later:

PostgreSQL search

can be replaced by:

* Typesense;
* Meilisearch;
* OpenSearch

without rewriting controllers/views/business workflows.

Do NOT introduce a dedicated search cluster prematurely.

---

# SMART DATA LOADING

No page should depend on loading all listings.

If the database contains:

100 listings

or:

10,000,000 listings,

a result page should conceptually ask only for the next required result set, e.g.:

24 items.

Listing cards should fetch only the fields needed to render cards.

Do not fetch full descriptions, all reviews, all photographs and unnecessary relationships for every search card.

Use eager loading intentionally.

Avoid over-fetching.

---

# LAZY MEDIA LOADING

Use responsive image markup.

Use:

* appropriate image dimensions;
* lazy loading;
* srcset/sizes where appropriate;
* CDN/object storage URLs;
* optimized formats.

Do not download images far below the viewport unnecessarily.

---

# DUPLICATE LISTINGS

Intentional duplicates are not allowed.

Create a moderation-friendly duplicate detection mechanism.

Potential signals:

* seller;
* similar title;
* matching image hashes;
* similar description;
* matching structured attributes.

Do NOT automatically permanently ban a user solely because a fuzzy detector thinks two listings are similar.

Flag suspicious cases.

Maintain strike/moderation history.

---

# MODERATION

Valid listings normally become publicly visible immediately.

Every newly published listing also enters an admin moderation queue as:

**Unreviewed**

Administrator can:

* mark reviewed;
* hide;
* suspend;
* remove;
* request changes;
* suspend user;
* ban user;
* record moderation notes.

Keep audit history.

---

# REPORTING

Allow reports against:

* listing;
* seller;
* review.

Store:

* reporter;
* reason;
* target;
* context;
* state;
* administrator resolution;
* timestamps.

Protect reporter privacy appropriately.

---

# ADMIN PANEL

Build a secure functional admin system.

Required modules:

* dashboard;
* users;
* individual sellers;
* businesses;
* business verification;
* listings;
* unreviewed listings;
* reports;
* review moderation;
* categories;
* subcategories;
* dynamic category attributes;
* locations;
* suspended accounts;
* banned accounts;
* audit logs;
* analytics;
* configuration.

Admin users use separate individual administrator accounts.

No shared universal "admin/admin" account.

---

# ADMIN SECURITY

Prepare/implement strong admin protections including:

* stricter rate limits;
* proper authorization;
* 2FA support;
* secure session controls;
* audit logs.

Audit important actions such as:

* account suspension;
* account banning;
* listing removal;
* review deletion;
* business verification;
* privilege changes.

---

# BUSINESS VERIFICATION

Support secure workflow for:

* business details;
* CUI/CIF;
* legal evidence;
* administrator review;
* approval/rejection;
* verification history.

Never expose private uploaded legal documents publicly.

---

# EMAIL

V1 uses email heavily.

Development:

use Mailpit.

Production:

use a proper transactional email provider via environment configuration.

Use Laravel queues.

Email events include:

* email verification;
* password reset;
* listing published;
* someone revealed seller contact;
* expiration in 3 days;
* listing expiration;
* review received;
* business verification result;
* later saved-search alerts.

HTTP requests should not wait for slow email sending.

---

# QUEUES

Heavy or non-immediate work should execute asynchronously.

Use queues for appropriate tasks including:

* image optimization;
* thumbnails;
* email;
* search indexing abstraction updates;
* analytics aggregation;
* other heavy work.

Do not make users wait for unnecessary background processing.

---

# REDIS

Use Redis intelligently for:

* cache;
* queues;
* rate limiting/supporting counters where appropriate;
* ephemeral analytics counters where useful.

Do not use Redis as the permanent source of truth.

PostgreSQL remains the primary durable business database.

---

# ANALYTICS

Do NOT fabricate statistics.

Per listing track useful real metrics such as:

* page views;
* reasonable unique views;
* contact reveals;
* favorites.

Avoid writing a database UPDATE to the same listing row for every single page view.

Prefer event/counter aggregation architecture using Redis/queues where appropriate.

Periodically persist aggregated metrics safely.

Seller dashboard should display meaningful real data.

Do not label something "live viewers" unless actual realtime presence exists.

---

# SELLER DASHBOARD

Support:

* active listings;
* drafts;
* sold;
* expired;
* reserved;
* listing views;
* contact reveals;
* favorites.

Quick actions:

* edit;
* mark sold;
* renew;
* remove.

Keep dashboard simple enough for inexperienced users.

---

# SEO

SEO is a first-class requirement.

Public listing/category pages must be server rendered.

Implement:

* good `<title>`;
* meta description;
* canonical URL;
* Open Graph metadata;
* semantic HTML;
* structured data where appropriate;
* breadcrumbs;
* XML sitemap;
* robots.txt;
* image alt attributes;
* clean URLs;
* category pages;
* location pages;
* pagination strategy;
* duplicate/filter indexation controls.

Example:

`/auto/dacia/duster/dacia-duster-2022-cluj-12345`

Do not create millions of useless thin indexed filter pages.

Keep architecture compatible with Google Search Console.

---

# PERFORMANCE ENGINEERING CONTRACT

Performance is mandatory, not a future cosmetic optimization.

Implement:

* appropriate database indexes;
* pagination;
* query optimization;
* intentional eager loading;
* zero known N+1 queries in critical flows;
* caching;
* Redis;
* object storage;
* optimized responsive images;
* queues;
* lazy loading;
* efficient response sizes.

Before production readiness, inspect query plans for critical searches.

Document important indexes and why they exist.

---

# LARGE DATASET TESTING

Do NOT test performance only with 20 listings.

Create realistic synthetic data tooling capable of producing large staging/development datasets.

Target test levels such as:

* 10,000 listings;
* 100,000 listings;
* eventually 1,000,000 synthetic listings for dedicated performance testing where local/staging resources permit.

Do not accidentally seed massive datasets during normal development startup.

Large load datasets must require explicit commands.

Performance-test:

* homepage;
* category results;
* location filter;
* price filter;
* multi-filter search;
* listing page;
* seller profile;
* admin moderation listing.

Measure query performance.

---

# LOAD TESTING

Add a reproducible load-testing strategy using k6 or an appropriate open-source equivalent.

Do not run destructive load tests against production.

Create scripts for staging/local performance testing.

Test increasing concurrency, e.g.:

* 50;
* 100;
* 500;
* 1,000 concurrent virtual users where infrastructure permits.

Measure:

* request latency;
* throughput;
* error rate;
* PostgreSQL latency;
* CPU;
* RAM;
* queue backlog;
* Redis performance.

Do not claim scalability because code "looks scalable".

Use measurements.

---

# GRACEFUL SCALING

V1 may run on one good VPS:

* Laravel;
* PostgreSQL;
* Redis;
* queue workers.

But design so these can later be separated without rewriting application business logic.

Future architecture should allow:

Cloudflare
→ load balancer
→ multiple stateless Laravel application nodes
→ dedicated PostgreSQL
→ dedicated Redis
→ dedicated search engine

Do not require this expensive architecture at launch.

But do not design against it.

Application web nodes should become as stateless as practical.

Do not depend on local filesystem user uploads.

---

# DATA LIFECYCLE

Listings expire after 30 days.

Do not allow unused images to accumulate forever without policy.

Design configurable lifecycle processes for:

* active listings;
* expired listings;
* removed listings;
* images;
* drafts;
* logs;
* analytics;
* moderation/legal retention where required.

Do NOT automatically delete legally/operationally necessary evidence without policy.

Document retention decisions.

---

# BACKUPS

Production documentation must require off-server backups.

At minimum include PostgreSQL backup strategy.

Backups should not live only on the same VPS as production.

Document:

* backup frequency;
* retention;
* restore instructions.

Before production readiness, perform an actual restoration test in a non-production environment.

A backup is not considered verified until restoration has been tested.

---

# SECURITY

Use secure Laravel practices and current OWASP principles.

Protect against:

* SQL injection;
* XSS;
* CSRF;
* IDOR;
* broken access control;
* mass assignment;
* insecure uploads;
* brute force;
* session abuse;
* privilege escalation;
* insecure reset flows;
* unauthorized admin operations.

Every mutation of user-owned data must enforce ownership server-side.

Never depend on hidden buttons as authorization.

Write authorization tests.

---

# GDPR / PRIVACY ARCHITECTURE

Prepare for GDPR compliance.

Support architecture for:

* privacy policy;
* cookie/consent needs where applicable;
* personal-data export;
* account deletion requests;
* administrator processing;
* appropriate retention rules;
* secure personal-data storage;
* auditability where necessary.

Account deletion should be a request workflow.

Do not build arbitrary permanent administrator veto power.

Handle legal retention separately from active account access.

---

# ACCOUNT DELETION

User can request deletion.

Admin workflow may show:

* account;
* request date;
* active listings;
* open reports;
* relevant legal/financial retention obligations.

Process according to defined privacy/legal rules.

---

# PROMOTED LISTINGS — PREPARE, DO NOT OVERBUILD

Do NOT build Meta Ads Manager in V1.

However, architecture may prepare for future:

* boost;
* featured listing;
* top placement;
* daily budget;
* lifetime budget;
* start/end date;
* spend;
* performance metrics.

Simple users should eventually receive a simple promotion experience.

Advanced business tools may come later.

---

# PAYMENTS

Do NOT create fake payment flows.

Future payments may use:

* Stripe;
* Visa/Mastercard through payment provider;
* Apple Pay where provider/device supports it.

Only implement real payments when provider configuration and requirements are available.

Never invent credentials.

---

# HOMEPAGE

Primary objectives:

* logo;
* large search;
* location;
* categories;
* obvious "Postează un anunț";
* relevant/recent listings;
* trust messaging.

Keep it clean.

Avoid banner overload.

---

# MOBILE NAVIGATION

A mobile bottom navigation may contain:

* Acasă;
* Caută;
* Postează;
* Favorite;
* Cont.

Do not add "Mesaje" because there is NO platform chat.

---

# THIRD-PARTY CONFIGURATION

If credentials for:

* Google OAuth;
* Facebook OAuth;
* Cloudflare;
* R2;
* production email;
* payment systems

are unavailable:

build the integration boundary correctly.

Document environment variables.

Do not invent secrets.

Do not falsely report the integration as fully active.

---

# QUALITY OVER SESSION LIMITS

This project is expected to continue across multiple Codex sessions.

DO NOT rush to fit the entire project into:

* one turn;
* a five-hour usage window;
* one weekly usage allowance;
* one context window.

Do not lower code quality because usage limits may interrupt work.

The project owner may later simply say:

**Continue**

Therefore preserve excellent continuity.

Prefer completing one smaller section correctly over completing five sections badly.

---

# CONTINUATION PROTOCOL

At every meaningful checkpoint:

* save source changes;
* keep repository buildable;
* commit finished logical units;
* update IMPLEMENTATION_PLAN.md;
* update tests;
* document known issues;
* document exact next task.

When the user says:

**Continue**

you must:

1. inspect repository;
2. read AGENTS.md;
3. read IMPLEMENTATION_PLAN.md;
4. inspect recent commits;
5. run relevant tests;
6. identify next unfinished milestone;
7. resume.

Do NOT rebuild completed work from scratch unless necessary.

---

# NEVER LEAVE A COMPLETED MILESTONE BROKEN

Do not say:

"Milestone complete"

if:

* application returns HTTP 500;
* migrations fail;
* critical tests fail;
* frontend compilation fails;
* queue configuration is broken;
* required local services cannot start.

A completed milestone must leave the repository in a usable state.

---

# MILESTONE PLAN

## Milestone 1 — Engineering foundation

Build:

* Laravel foundation;
* PostgreSQL;
* Redis;
* Docker development environment;
* local object-storage strategy;
* Mailpit;
* Blade/Vite foundation;
* localization;
* base responsive layout;
* README;
* AGENTS.md;
* IMPLEMENTATION_PLAN.md;
* architecture docs;
* START/STOP Windows scripts;
* health endpoint/checks;
* testing foundation.

Do not build fake marketplace features.

## Milestone 2 — Authentication

* registration;
* login/logout;
* email verification;
* password reset;
* OAuth integration architecture;
* account types;
* account suspension/ban foundations.

## Milestone 3 — Profiles

* individual profiles;
* business profiles;
* profile image;
* public profile;
* privacy.

## Milestone 4 — Categories

* hierarchical categories;
* subcategories;
* dynamic attributes;
* admin management.

## Milestone 5 — Posting wizard / drafts

* multi-step flow;
* validation;
* autosave drafts;
* preview.

## Milestone 6 — Image pipeline

* 8-image maximum;
* secure validation;
* optimized variants;
* object storage;
* reorder;
* delete;
* primary image.

## Milestone 7 — Listing lifecycle

* publish;
* public pages;
* edit;
* states;
* expiration;
* renewal/sold/reserved.

## Milestone 8 — Search

* full filtering;
* indexed queries;
* pagination;
* search abstraction.

## Milestone 9 — Contact reveal

* phone/email;
* login requirement;
* protected backend reveal;
* anti-bot;
* Turnstile structure;
* rate limits;
* analytics;
* seller email notification.

## Milestone 10 — Favorites / recent / saved searches

Build all three completely.

## Milestone 11 — Reviews / reputation

* stars;
* max 800 characters;
* interaction eligibility;
* seller replies;
* reporting;
* reputation signals.

## Milestone 12 — Business verification

* legal info;
* secure documents;
* approval workflow;
* badges.

## Milestone 13 — Reporting / moderation

* reports;
* unreviewed queue;
* sanctions;
* moderation history.

## Milestone 14 — Admin system

Finish the full functional administration system.

## Milestone 15 — Email / scheduled jobs

* queues;
* expiration reminders;
* scheduler;
* saved-search architecture.

## Milestone 16 — Seller analytics

* views;
* unique/reasonable view metrics;
* contact reveals;
* favorites;
* dashboard.

## Milestone 17 — SEO

* metadata;
* schemas;
* sitemap;
* robots;
* canonical;
* breadcrumb;
* clean URLs;
* category/location SEO.

## Milestone 18 — Performance engineering

* cache;
* indexes;
* N+1 audit;
* query plans;
* payload review;
* image review.

## Milestone 19 — High-scale synthetic testing

* dataset generators;
* large database testing;
* search benchmarks.

## Milestone 20 — Load testing

* k6 scripts;
* concurrency tests;
* performance documentation.

## Milestone 21 — Security review

* authorization audit;
* uploads;
* sessions;
* admin;
* rate limiting;
* security tests.

## Milestone 22 — Accessibility / mobile QA

* touch;
* keyboard;
* contrast;
* responsive layouts;
* labels;
* errors.

## Milestone 23 — Production preparation

Document/configure:

* Nginx;
* PHP-FPM;
* PostgreSQL;
* Redis;
* queue workers;
* Supervisor/systemd;
* scheduler;
* Cloudflare;
* R2;
* SSL;
* logs;
* monitoring;
* backups;
* restoration;
* deployment.

---

# AFTER EVERY MILESTONE

Perform:

1. migrations;
2. automated tests;
3. formatter/linter;
4. frontend build;
5. fix errors;
6. browser verification where available;
7. update docs;
8. update implementation plan;
9. logical Git commit.

Then report:

COMPLETED:
...

TESTS:
...

TEST RESULTS:
...

BROWSER VERIFIED:
...

PERFORMANCE NOTES:
...

IMPORTANT DECISIONS:
...

KNOWN ISSUES:
...

EXTERNAL CONFIGURATION STILL NEEDED:
...

NEXT MILESTONE:
...

LOCAL PREVIEW:
...

START COMMANDS:
...

Do not begin another milestone while known blocking failures remain.

---

# FIRST TASK — START NOW

Do NOT build the entire marketplace immediately.

Perform this exact sequence:

1. Inspect the repository.
2. Inspect `docs/design-references/` if present.
3. Confirm the repository is safe to initialize as a new application.
4. Initialize/configure Laravel.
5. Establish the Docker-based development environment.
6. Create PostgreSQL configuration.
7. Create Redis configuration.
8. Configure Mailpit.
9. Configure local S3-compatible object storage if appropriate.
10. Create `.env.example`.
11. Create `.gitignore`.
12. Create README.md.
13. Create AGENTS.md.
14. Create IMPLEMENTATION_PLAN.md.
15. Create docs/architecture.md.
16. Create docs/testing.md.
17. Create START-OLXv2.bat.
18. Create STOP-OLXv2.bat.
19. Implement responsive application shell only — no fake marketplace features.
20. Add Romanian/English localization foundation.
21. Configure automated testing.
22. Run migrations.
23. Run tests.
24. Run frontend build.
25. Start the complete local application.
26. Open and verify it in a browser if browser access is available.
27. Fix all blocking errors.
28. Commit Milestone 1.
29. Report the exact local URL and start commands.
30. STOP.

Do NOT begin Milestone 2 during this first task.

Do not create mockups.

Do not create placeholder marketplace screens pretending to be functional.

Build the engineering foundation correctly.

Once Milestone 1 is fully completed, wait for the user to say:

**Continue**
