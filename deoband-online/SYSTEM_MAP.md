# Deoband Online - System Map

This file explains which module handles what and where to edit each feature.

## Root
- `deoband-online.php`: Main loader, activation bootstrapping, constants, shortcode, asset registration.
- `SYSTEM_MAP.md`: This architecture map.

## Core Includes (`includes/`)
- `database.php`: Centralized table creation + default option seeding (runs on plugin activation).
- `helpers.php`: Shared helper utilities.
- `security.php`: Shared security helpers.
- `logger.php`: Persistent DB logging (AI / IMPORT / PAYMENT / ERROR).
- `rate-limit.php`: Per-user/IP rate limiting controls.

## Admin Layer (`admin/`)
- `admin-panel.php`: Registers dashboard and all submenu pages.
- `settings.php`: API settings + system controls (search weights, AI, rate limits, trending).
- `payments.php`: Manual UPI + Razorpay-ready settings + disclaimer text.
- `content-builder.php`: Flexible content blocks.

## Feature Modules (`modules/`)
- `masail/masail.php`: Q&A CRUD.
- `search/search.php`: Tokenized stopword-filtered weighted search + fuzzy matching.
- `ai/ai.php`: Grok/OpenAI/Gemini integration with retries, timeout, fallback, DB save, logging.
- `tokens/tokens.php`: Token balance logic + payment verification + duplicate credit prevention.
- `subscription/subscription.php`: Monthly plans and limits.
- `affiliate/affiliate.php`: Referral tracking and commission settings.
- `trending/trending.php`: Trend score with freshness penalty.
- `foryou/foryou.php`: Personalized feed from tracked searches/clicks/likes.
- `notifications/notifications.php`: Broadcast/event notifications.
- `api/api.php`: REST API routes.
- `import/cron-import.php`: Source-based DOM scraper (deoband/binori), hash dedupe, logs, run-limit.
- `complaint/complaint.php`: Chat-style complaint support.
- `news/news.php`: RSS news reader.
- `prayer/prayer.php`: Prayer time API + manual override.
- `likeshare/likeshare.php`: Like/share counters.
- `language/language.php`: Multi-language engine (Hindi/English/Urdu), caching, provider control.

## Templates (`templates/`)
- `frontend.php`: Front display wrapper.
- `masail-view.php`: Single masail card UI.
- `admin-system-controls.php`: Control panel for search/AI/rate-limit/trending.
- `admin-translation-settings.php`: Translation module settings.
- `admin-*.php`: Remaining admin pages.
