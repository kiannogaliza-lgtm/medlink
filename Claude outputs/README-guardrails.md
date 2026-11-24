# MedLink agent guardrails

These files add automatic checks and approval gates for every database command and file write an AI coding agent makes in `C:\laragon\www\techyknowloadeia1`.

## What you get

| Situation | Claude Code (hooks, enforced) | Copilot (settings + instructions) |
|---|---|---|
| Any file edit or create | **Asks you** and shows the file and reason | Asks you (edit auto-approve off) |
| `.env`, uploads, vendor, build output, the guard files | **Blocked** | Instructed never to touch |
| Editing an existing migration | Asks, with an "old migration" warning | Instructed never to |
| `php artisan migrate` / `rollback` | **Blocked unless a `.sql` backup newer than 60 min is in `C:\laragon-backups`**, then asks | Always asks; told to check for a backup |
| `migrate:fresh/refresh/reset`, `db:wipe`, `DROP`, `TRUNCATE` | **Blocked** | Always asks; instructed never to run |
| `tinker`, `db:seed`, `mysql ...` | Asks (flags write SQL) | Always asks |
| `php artisan test` | Runs only while `phpunit.xml` forces SQLite `:memory:` | Instructed |
| `git push` | **Blocked** | Always asks |
| `git commit`, `composer`/`npm install`, `rm`/`del`/`mv`, `>` redirects | Asks | Asks |
| `php -l`, `migrate --pretend`, `route:list`, `node --check`, `npm run build`, `git status/diff` | Run without asking | Run without asking |
| **After every write** | `php -l`, `node --check`, `migrate --pretend` for migrations, plus house-rule scans (old `09` regex, logged OTP codes, `env('HTTPSMS_API_KEY')` outside config). A failure is sent back to the agent to fix | Instructed to run and report |
| **End of each turn** | Re-lints every file edited this session, runs `route:list` if routes changed and `npm run build` if Vue/JS changed. The agent can't finish while something fails | Instructed |

## Install

Copy into the project root, keeping the folders:

```
techyknowloadeia1\
  .claude\settings.json
  .claude\hooks\_lib.php
  .claude\hooks\guard-bash.php
  .claude\hooks\guard-write.php
  .claude\hooks\post-edit-check.php
  .claude\hooks\stop-check.php
  .github\copilot-instructions.md
  .vscode\settings.json        (merge it if you already have one)
```

Add to `.gitignore`:

```
.claude/hooks/.session-edits
.claude/settings.local.json
```

Create the backup folder once: `mkdir C:\laragon-backups`

Requirements: `php` and `node` on PATH. Laragon's terminal has both. Claude Code on Windows runs hooks through Git Bash, which provides `$CLAUDE_PROJECT_DIR`.

## Back up, then migrate (what the guard expects)

```
cmd /c "mysqldump -u root <your_db_name> > C:\laragon-backups\medlink_%date:~10,4%%date:~4,2%%date:~7,2%.sql"
```

Run it from `cmd`, not PowerShell, because PowerShell's `>` writes UTF-16. Approve the agent's `php artisan migrate` within 60 minutes of the backup, or run it yourself.

## Verify after installing

1. Claude Code: run `/hooks`. You should see PreToolUse (Bash, Edit|Write...), PostToolUse and Stop.
2. Ask the agent to run `php artisan migrate`. With no recent backup it should be blocked with the backup message.
3. Ask it to add a blank line to any controller. It should ask first, then report `php -l` passing.
4. Copilot: open the Chat settings and confirm `chat.tools.edits.autoApprove` and `chat.tools.terminal.autoApprove` are recognized. These setting names follow current VS Code docs; if your version greys them out, update VS Code or rely on `.github/copilot-instructions.md`.

## Limits (be aware)

- Copilot has no hook layer here, so its gates depend on VS Code settings plus the instruction file. Claude Code enforces them in code.
- The command guard matches patterns. It catches normal commands but not deliberately disguised ones; your approval prompt is the final check.
- To turn the guard off temporarily, rename `.claude/settings.json`. The agent itself can't change it.
