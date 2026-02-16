# Invoice Template Editor - Development Plan

## 🎯 Goal
Drag-and-drop invoice/quote template editor met:
- Logo upload
- Background image upload (full PDF)
- Field positioning (absolute x, y, width, height)
- Per template opslaan
- PDF generation met Spatie Browsershot + Tailwind

## 📊 Database Schema

### `invoice_templates` table
```sql
CREATE TABLE invoice_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    is_default BOOLEAN DEFAULT 0,
    logo_path VARCHAR(255) NULL,
    background_path VARCHAR(255) NULL,
    field_positions JSON NULL,  -- Store all field positions
    page_size VARCHAR(10) DEFAULT 'A4',  -- A4, Letter, etc.
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX(is_default)
);
```

### Field Positions JSON Structure
```json
{
  "invoice_number": {"x": 450, "y": 50, "width": 200, "height": 30, "fontSize": 14},
  "invoice_date": {"x": 450, "y": 90, "width": 200, "height": 30, "fontSize": 12},
  "company_name": {"x": 50, "y": 50, "width": 300, "height": 40, "fontSize": 18, "bold": true},
  "company_address": {"x": 50, "y": 100, "width": 300, "height": 60, "fontSize": 11},
  "client_name": {"x": 50, "y": 200, "width": 300, "height": 30, "fontSize": 14, "bold": true},
  "client_address": {"x": 50, "y": 240, "width": 300, "height": 60, "fontSize": 11},
  "logo": {"x": 400, "y": 20, "width": 150, "height": 80},
  "items_table": {"x": 50, "y": 350, "width": 700, "height": 300},
  "subtotal": {"x": 550, "y": 670, "width": 200, "height": 25},
  "tax": {"x": 550, "y": 700, "width": 200, "height": 25},
  "total": {"x": 550, "y": 730, "width": 200, "height": 30, "fontSize": 16, "bold": true},
  "payment_terms": {"x": 50, "y": 780, "width": 700, "height": 60, "fontSize": 10}
}
```

## 🗓️ 5-Day Development Plan

### DAG 1: Vandaag (10 feb, 22:15-23:30)
**Focus: Research + Database Foundation**
- [x] Research beste practices (absolute positioning ✓)
- [ ] Create migration: `invoice_templates` table
- [ ] Seed default template (basic positions)
- [ ] Test migration

**Time: 1 hour max (token management)**

---

### DAG 2: Woensdag 11 feb (09:00-10:30 + 14:00-15:30)
**Focus: Template CRUD + Upload**
- [ ] Template model + controller
- [ ] Routes: `/templates` CRUD
- [ ] Blade views: index, create, edit
- [ ] Logo upload (storage/logos)
- [ ] Background upload (storage/backgrounds)
- [ ] Image validation (jpg, png, max 5MB)

**Deliverable:** Working template management

**Time: 2x 1.5h = 3 hours**

---

### DAG 3: Donderdag 12 feb (09:00-11:00 + 14:00-16:00)
**Focus: Drag-and-Drop Canvas**

**Morning (09:00-11:00):**
- [ ] Install interact.js (via npm)
- [ ] Create `template-editor.blade.php`
- [ ] A4 canvas (794x1123 pixels @ 96 DPI)
- [ ] Background image preview
- [ ] Logo placement preview

**Afternoon (14:00-16:00):**
- [ ] Field library sidebar (draggable elements)
- [ ] Drop zones met snap-to-grid
- [ ] Position tracking (Alpine.js state)
- [ ] Visual feedback (borders, handles)

**Deliverable:** Interactive canvas met drag-and-drop

**Time: 4 hours**

---

### DAG 4: Vrijdag 13 feb (09:00-11:00 + 14:00-16:00)
**Focus: Save/Load + Template Selection**

**Morning (09:00-11:00):**
- [ ] Save field positions to JSON
- [ ] Load saved template
- [ ] Update existing template
- [ ] Template preview mode

**Afternoon (14:00-16:00):**
- [ ] Template selector in invoice form
- [ ] Template selector in quote form
- [ ] Default template logic
- [ ] Template duplication feature

**Deliverable:** Complete save/load + integration

**Time: 4 hours**

---

### DAG 5: Zaterdag 14 feb (10:00-13:00 + 15:00-17:00)
**Focus: PDF Generation + Testing**

**Morning (10:00-13:00):**
- [ ] Install Spatie Browsershot: `composer require spatie/browsershot`
- [ ] Custom Blade template: `invoice-pdf.blade.php`
- [ ] Absolute positioning with Tailwind
- [ ] Background image rendering (full-page)
- [ ] Logo rendering (positioned)

**Afternoon (15:00-17:00):**
- [ ] Field data injection (invoice/quote data)
- [ ] Items table rendering
- [ ] Test various templates
- [ ] Edge cases (missing logo, no background)
- [ ] Performance optimization

**Deliverable:** Working PDF generation

**Time: 5 hours**

---

## 🛠️ Tech Stack

**Frontend:**
- **Alpine.js** - State management + reactivity
- **interact.js** - Drag-and-drop library
- **Tailwind CSS** - Styling + PDF layout
- **Flowbite** - UI components (sidebar, modals)

**Backend:**
- **Laravel 12** - Framework
- **Spatie Browsershot** - PDF generation (uses Puppeteer)
- **Storage** - Local disk for uploads

**PDF Rendering:**
- **Tailwind in Blade** - Absolute positioning via utility classes
- **Background CSS** - `background-image: url()` on full page
- **Logo** - `<img>` tag with absolute positioning

## 📐 A4 Dimensions

- **Page size:** 210mm x 297mm
- **Pixels @ 96 DPI:** 794px x 1123px
- **Canvas scale:** 0.7 (for screen fit) → 556px x 786px display
- **Field coordinates:** Store in full resolution (794x1123)
- **PDF output:** Full A4 @ 300 DPI

## 🎨 Field Types

**Available Fields:**
1. **Company Info:** name, address, email, phone, logo
2. **Client Info:** name, address, email
3. **Invoice Meta:** number, date, due_date, terms
4. **Items Table:** description, qty, price, total (fixed table)
5. **Totals:** subtotal, tax, discount, total
6. **Footer:** payment_terms, notes, thank_you

**Field Properties:**
- `x, y` - Position (pixels from top-left)
- `width, height` - Dimensions
- `fontSize` - Text size (px)
- `bold` - Font weight
- `align` - left/center/right

## 🚀 Implementation Notes

**Drag-and-Drop Flow:**
1. User drags field from sidebar → canvas
2. interact.js tracks position
3. Alpine.js stores in reactive state
4. Save button → POST to `/templates/{id}/positions`
5. Controller updates `field_positions` JSON

**PDF Generation Flow:**
1. Select template in invoice form
2. Click "Generate PDF"
3. Load template + field positions
4. Render Blade view with absolute positioning
5. Browsershot converts HTML → PDF
6. Download file

**Token Management:**
- Each session: 1-1.5 hour max
- Daily updates via EOD summary
- Split work in small, testable chunks
- Commit after each milestone

## ✅ Success Criteria

- [ ] Create/edit/delete templates
- [ ] Upload logo + background
- [ ] Drag-and-drop field positioning
- [ ] Save/load field positions per template
- [ ] Template selection in invoice/quote forms
- [ ] PDF generation with custom layout
- [ ] Background image in PDF
- [ ] Logo positioned correctly
- [ ] All fields render at saved positions

**Target: All done by Feb 14, 17:00**

---

## 📝 Daily Progress Log

### Dag 1 (10 feb) - COMPLETED ✅
**Time: 22:15-22:45 (30 min)**
- Research completed ✓
- Plan created ✓
- Migration created ✓
- Seeder with 2 default templates ✓
- Model with casts & helpers ✓
- Database tested ✓

**Deliverables:**
- `invoice_templates` table live
- 2 templates seeded (Standaard + Modern)
- InvoiceTemplate model ready

**Next (Dag 2):** Template CRUD + Upload functionality
