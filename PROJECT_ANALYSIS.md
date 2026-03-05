# Project Analysis: `vgbm`

## Current Repository State

This repository is currently a **bootstrap/skeleton** and does not yet contain application source code.

### Files present
- `README.md`
- `LICENSE` (Apache 2.0)

### What can be inferred
- Project name: `vgbm`
- Intended domain: **Real Estate Management Solution**

### What is missing (for implementation analysis)
- No backend/frontend source code
- No dependency manifests (`package.json`, `requirements.txt`, `pyproject.toml`, etc.)
- No infrastructure/config files (`Dockerfile`, CI workflows, env examples)
- No architecture or setup documentation

---

## Practical Conclusion

At this point, this repository is best treated as a **project shell** ready for proper engineering workflow setup.

Because code is not present yet, analysis can only cover:
1. Project intent (real estate management)
2. Repository readiness
3. Recommended GitHub development workflow

---

## Recommended GitHub Workflow (Start Here)

## 1) Branching model
- Keep `main` protected for stable history.
- Use short-lived feature branches:
  - `feat/<scope>`
  - `fix/<scope>`
  - `chore/<scope>`

## 2) Pull request discipline
- Never push directly to `main`.
- Open PRs for all changes.
- Require at least 1 review before merge.
- Enable squash merge to keep history clean.

## 3) Basic repo standards to add immediately
- `CONTRIBUTING.md` with branch + PR rules
- `.github/pull_request_template.md`
- `.github/ISSUE_TEMPLATE/` templates (bug + feature)
- `.gitignore` aligned to your stack
- `SECURITY.md` (how to report vulnerabilities)

## 4) CI guardrails
Add GitHub Actions so every PR runs checks:
- Lint
- Unit tests
- Build

(Exact jobs depend on your actual stack once code is uploaded.)

## 5) Initial project structure recommendation
When re-uploading code, target a structure like:

```text
/docs
/backend
/frontend
/infrastructure
.github/workflows
```

## 6) Definition of Done for each PR
- Code compiles/runs
- Tests pass
- Docs updated
- No secrets committed
- Reviewer approved

---


## Additional Confirmed Domain Requirement

A newly confirmed requirement is multi-currency support:
- Each portfolio has a default currency (example: `EUR`).
- Every amount field (including contract rent and unit-level amounts) must still allow manual per-record currency selection.

See `CURRENCY_REQUIREMENTS.md` for the detailed specification and acceptance criteria.
- Implementation details are captured in `docs/currency_implementation_plan.md`.

---

## What to do next

1. Re-upload/push the **full codebase** (the zip appears incomplete in this repository).
2. Add the missing GitHub repo standards listed above.
3. Once code exists, run a technical analysis pass:
   - architecture map
   - dependency audit
   - risk list
   - implementation roadmap

