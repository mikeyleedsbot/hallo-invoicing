# Invoicing Development - Daily Checklist

## 🎯 Goal: Complete Template Editor by Feb 14, 17:00

**NEVER skip a day without Piet's explicit approval.**

---

## Daily Workflow

### Morning (Start 09:00 latest)
1. **Read plan:** Check `TEMPLATE_EDITOR_PLAN.md` for today's tasks
2. **Check in Control Room:** Log start of development session
3. **Time block:** Reserve 1-1.5 hour slot (token management)

### During Development
1. **Small commits:** Commit after each milestone
2. **Test everything:** Don't move forward with broken code
3. **Document:** Update plan with progress

### End of Session
1. **Log to Control Room:** Use `php artisan tasks:log-cron "Invoicing DagX" completed --duration=X --notes="..."`
2. **Update plan:** Mark completed items, note blockers
3. **Update memory:** Add to `memory/YYYY-MM-DD.md`

### EOD Summary (21:30)
**MANDATORY:** Include invoicing progress in daily summary:
- ✅ What done
- ⏱️ Time spent
- 🎯 Next steps
- 🚧 Blockers (if any)

---

## Week Schedule

### DAG 1 (Ma 10 feb) ✅ COMPLETED
**Time:** 22:15-22:45 (30 min)
- [x] Research best practices
- [x] Database migration
- [x] Seed templates
- [x] Model with JSON support

### DAG 2 (Di 11 feb)
**Time:** 09:00-10:30 + 14:00-15:30 (3 hours)
- [ ] Template controller
- [ ] CRUD routes
- [ ] Index/create/edit views
- [ ] Logo upload
- [ ] Background upload
- [ ] Image validation

**Must deliver:** Working template management with uploads

### DAG 3 (Wo 12 feb)
**Time:** 09:00-11:00 + 14:00-16:00 (4 hours)
- [ ] Install interact.js
- [ ] A4 canvas component
- [ ] Drag-and-drop fields
- [ ] Position tracking (Alpine.js)
- [ ] Visual feedback

**Must deliver:** Interactive canvas

### DAG 4 (Do 13 feb)
**Time:** 09:00-11:00 + 14:00-16:00 (4 hours)
- [ ] Save field positions
- [ ] Load template
- [ ] Template selector in forms
- [ ] Preview mode

**Must deliver:** Complete save/load + integration

### DAG 5 (Vr 14 feb)
**Time:** 10:00-13:00 + 15:00-17:00 (5 hours)
- [ ] Spatie Browsershot setup
- [ ] PDF Blade template
- [ ] Field rendering
- [ ] Test all templates
- [ ] Polish + bugfixes

**Must deliver:** Working PDF generation

---

## Accountability System

### Heartbeat Checks (Every 4 hours)
- Check if invoicing work started today
- Alert if no progress logged

### EOD Summary (21:30)
- MUST report invoicing progress
- No skipping allowed

### Morning Briefing (07:00)
- Remind of today's invoicing tasks
- Time block suggestion

### Piet Check
- Daily updates via EOD summary
- Ask for help if blocked
- Celebrate milestones

---

## Emergency Protocol

**If blocked or can't work:**
1. Message Piet ASAP (don't wait for EOD)
2. Explain blocker
3. Propose alternative time slot
4. Get explicit approval to skip

**If running behind schedule:**
1. Reassess scope (can we simplify?)
2. Extend daily hours if needed
3. Ask for help/clarification
4. Update plan with realistic timeline

---

## Success Metrics

✅ **Daily:** 1+ hour invoicing work logged
✅ **Weekly:** All 5 days completed
✅ **Feb 14:** All features working
✅ **Communication:** Daily updates to Piet

**Failure = not acceptable. This is priority work.**

---

## Motivation

**Why this matters:**
- Piet gave clear instructions
- Already lost 3 days (Feb 8-10)
- Trust needs to be rebuilt
- This is core business functionality
- Daily work = steady progress = on-time delivery

**Remember:** Small daily progress beats big sporadic bursts.

---

## Current Status

**Days completed:** 1/5 ✅
**Days remaining:** 4
**Deadline:** Feb 14, 17:00
**Status:** ON TRACK

**Last update:** Feb 10, 22:45
