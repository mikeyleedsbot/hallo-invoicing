# Invoice Template Editor - User Guide

**Version:** 1.0  
**Date:** February 17, 2026  
**Status:** ⚠️ **Template system functional - Integration with invoices/quotes incomplete**

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [Creating a Template](#creating-a-template)
4. [Uploading Logo & Background](#uploading-logo--background)
5. [Positioning Fields (Drag-and-Drop)](#positioning-fields-drag-and-drop)
6. [Saving Positions](#saving-positions)
7. [Generating Test PDFs](#generating-test-pdfs)
8. [Setting Default Template](#setting-default-template)
9. [Troubleshooting](#troubleshooting)
10. [⚠️ Known Limitations](#known-limitations)

---

## Overview

The Invoice Template Editor allows you to create custom PDF templates for your invoices and quotes with:

- **Custom Logos** - Upload your company logo
- **Background Images** - Add watermarks or full-page backgrounds
- **Drag-and-Drop Field Positioning** - Visually position all invoice fields
- **Multiple Templates** - Create different templates for different purposes
- **Live Preview** - Test PDFs with mock data before using

### What You Can Do

✅ Create unlimited custom templates  
✅ Upload logo images (PNG, JPG)  
✅ Upload background images (JPG, PNG)  
✅ Drag-and-drop fields onto A4 canvas  
✅ Resize and reposition all fields  
✅ Generate test PDFs with sample data  
✅ Set a default template  
✅ Duplicate existing templates  

### ⚠️ What's Not Yet Working

❌ **Selecting templates when creating invoices** (integration incomplete)  
❌ **Selecting templates when creating quotes** (integration incomplete)  
❌ Real invoice/quote PDFs don't use custom templates yet  

> **Note:** The template system is fully functional, but the final step of connecting it to invoice/quote generation is not yet implemented. You can create and test templates, but they won't be used for real invoices until integration is completed.

---

## Getting Started

### Accessing Templates

1. Log in to Hallo Invoicing
2. Click **"Templates"** in the left sidebar (under "Instellingen")
3. You'll see the Template overview page

### Template Overview Page

The templates page shows:
- **Template cards** with preview thumbnails
- **Logo indicator** (✓ Logo / Geen logo)
- **Page size** (A4)
- **Action buttons:**
  - 🖊️ **Bewerk** - Edit template name/settings
  - 🎨 **Layout** - Open drag-and-drop editor
  - ✓ **Standaard** - Set as default template
  - 🗑️ **Delete** - Remove template

---

## Creating a Template

### Method 1: From Scratch

1. Click **"+ Nieuwe Template"** (blue button, top right)
2. Fill in template details:
   - **Naam** (Name): Give it a descriptive name (e.g., "Modern Blue", "Classic", "Quotation Template")
   - **Logo** (optional): Upload logo now or later
   - **Achtergrond** (Background, optional): Upload background now or later
   - **Paginaformaat**: Select page size (A4 default)
3. Click **"Template Aanmaken"**
4. You'll be redirected to the template list
5. Click **"Layout"** to start positioning fields

### Method 2: Duplicate Existing Template

1. Find a template you like
2. Click **"Bewerk"** (Edit)
3. Scroll to bottom, click **"Dupliceer Template"**
4. Modify the duplicate as needed

---

## Uploading Logo & Background

### Logo Requirements

- **Formats:** PNG, JPG, JPEG
- **Max Size:** 5 MB
- **Recommended:** 
  - Transparent background (PNG)
  - Resolution: 300-600px width
  - Aspect ratio: 2:1 or 3:1 (landscape)

### Background Requirements

- **Formats:** JPG, PNG
- **Max Size:** 5 MB
- **Recommended:** 
  - A4 dimensions: 2480 x 3508 pixels (300 DPI) or 794 x 1123 pixels (96 DPI)
  - Use subtle watermarks - text must remain readable
  - Consider using low opacity (20-40%)

### How to Upload

**Option 1: During Template Creation**
1. Click **"+ Nieuwe Template"**
2. Use **"Kies bestand"** buttons for Logo and/or Achtergrond
3. Select your file
4. Click **"Template Aanmaken"**

**Option 2: Edit Existing Template**
1. Click **"Bewerk"** on any template card
2. Use **"Kies bestand"** buttons
3. Click **"Bijwerken"** (Update)

### Viewing Uploaded Files

Once uploaded:
- **Logo** appears in template preview thumbnail
- **Logo indicator** changes from "Geen logo" to "Logo ✓"
- Files are stored in `storage/app/public/logos/` and `storage/app/public/backgrounds/`

---

## Positioning Fields (Drag-and-Drop)

### Opening the Editor

1. Click **"Layout"** button on any template card
2. The editor opens with:
   - **Left sidebar:** Available fields
   - **Center canvas:** A4 preview (100% scale)
   - **Bottom buttons:** Save and Test PDF

### Understanding the Canvas

- **A4 Canvas:** Represents your PDF at actual size
- **Dashed borders:** Show field boundaries
- **Background:** Your uploaded background image (if any)
- **Logo:** Your uploaded logo (if any)

### Available Fields

Fields are organized by category:

**Bedrijfsgegevens (Company Info):**
- Bedrijfsnaam (Company Name)
- Bedrijfsadres (Company Address)
- Bedrijfs E-mail
- Bedrijfs Telefoon

**Klantgegevens (Client Info):**
- Klantnaam (Client Name)
- Klantadres (Client Address)
- Klant E-mail

**Factuur Info (Invoice Info):**
- Factuurnummer (Invoice Number)
- Factuurdatum (Invoice Date)
- Vervaldatum (Due Date)
- Referentie (Reference)

**Overige (Other):**
- Artikelen Tabel (Items Table)
- Subtotaal (Subtotal)
- BTW (VAT)
- Totaal (Total)
- Betalingsvoorwaarden (Payment Terms)

### Adding Fields

1. Click on any field in the sidebar (e.g., "Bedrijfsnaam")
2. The field appears on the canvas
3. The sidebar button becomes disabled and shows ✓

### Moving Fields

1. Click and hold on any field box (dashed border)
2. Drag to desired position
3. Release to drop
4. Position updates automatically

### Resizing Fields

1. Hover over field border until resize cursor appears
2. Click and drag border handles
3. Resize width and/or height as needed

### Editing Field Properties

1. Click the **✎ (blue pencil)** button on any field
2. A modal opens with options:
   - Font size
   - Font weight (bold)
   - Text alignment
3. Click **"Opslaan"** to apply changes

### Removing Fields

1. Click the **✕ (red X)** button on any field
2. Field is removed from canvas
3. Field becomes available again in sidebar

### Tips for Best Results

- **Start with key elements:** Position logo, company name, and invoice number first
- **Use grid alignment:** Align related fields vertically/horizontally
- **Leave breathing room:** Don't pack fields too tightly
- **Test readability:** Ensure background doesn't obscure text
- **Items table:** Give this plenty of space (it expands with items)
- **Totals section:** Keep subtotal/tax/total close together (right-aligned is common)

---

## Saving Positions

### When to Save

Save your field positions:
- After adding new fields
- After moving fields
- After resizing fields
- After editing field properties
- Before closing the editor

### How to Save

1. Click **"💾 Posities Opslaan"** (blue button, bottom right)
2. A success message confirms save
3. Positions are stored in database as JSON

### What Gets Saved

For each field:
```json
{
  "x": 450,           // Horizontal position (pixels from left)
  "y": 50,            // Vertical position (pixels from top)
  "width": 200,       // Field width (pixels)
  "height": 30,       // Field height (pixels)
  "fontSize": 14,     // Text size (optional)
  "bold": true,       // Font weight (optional)
  "align": "left"     // Text alignment (optional)
}
```

### Loading Saved Positions

Positions load automatically when you open the editor:
1. Click **"Layout"** on a template
2. All previously saved fields appear in their saved positions
3. Continue editing from where you left off

---

## Generating Test PDFs

### Test Route (Direct Access)

Test PDFs with mock data:
1. Navigate to: `http://localhost:8000/templates/{id}/test-pdf`
2. Replace `{id}` with template ID (e.g., `/templates/3/test-pdf`)
3. PDF opens in browser

### From Editor

1. Open template editor (click **"Layout"**)
2. Click **"📄 PDF Testen"** (green button, bottom left)
3. PDF opens in new tab with sample data

### Sample Data Used

Test PDFs include:
- **Company:** Hallo ICT B.V., Teststraat 123, Amsterdam
- **Client:** Test Klant B.V., Rotterdam
- **Invoice:** INV-2026-001, dated today
- **Items:** 3 sample products (Webhosting, E-mail, SSL)
- **Totals:** €104.90 subtotal, €22.03 tax, €126.93 total

### What to Check

Review test PDFs for:
- ✅ Logo renders correctly (size, position)
- ✅ Background doesn't obscure text
- ✅ All fields are readable
- ✅ Field positions match your design
- ✅ Items table has enough space
- ✅ Totals are clearly visible
- ✅ No overlapping text
- ✅ Professional appearance

---

## Setting Default Template

### What is the Default Template?

The default template is automatically selected when creating invoices/quotes (once integration is complete).

### How to Set Default

1. Go to Templates overview page
2. Find the template you want as default
3. Click the **"Standaard"** button (blue when active)
4. Badge appears: **"✓ Standaard"**
5. Other templates automatically lose default status (only one can be default)

### Current Issue

⚠️ **Bug Found:** Multiple templates may show "Standaard" badge simultaneously. Only the last one set is actually the default in the database.

---

## Troubleshooting

### Logo Not Showing in PDF

**Possible causes:**
1. Logo not uploaded → **Solution:** Edit template, upload logo
2. Logo path missing → **Solution:** Re-upload logo
3. Logo field not added to canvas → **Solution:** Open editor, add "Logo" field from sidebar
4. File permissions → **Solution:** Check `storage/app/public/logos/` is writable

### Background Not Visible

**Possible causes:**
1. Background not uploaded
2. Background too subtle/light
3. File path incorrect

**Solutions:**
- Re-upload background image
- Use higher contrast/opacity
- Check file exists in `storage/app/public/backgrounds/`

### Fields Not Saving

**Symptoms:** Positions reset when reopening editor

**Solutions:**
1. Click **"💾 Posities Opslaan"** after changes
2. Check browser console for errors
3. Verify database permissions
4. Try refreshing page and re-saving

### PDF Not Generating

**Error messages:**
- "500 Server Error"
- "Template not found"

**Solutions:**
1. Verify template exists (check ID in URL)
2. Check server logs: `storage/logs/laravel.log`
3. Ensure dompdf package installed: `composer require barryvdh/laravel-dompdf`

### Drag-and-Drop Not Working

**Solutions:**
1. Refresh page (F5)
2. Clear browser cache
3. Try different browser (Chrome recommended)
4. Check JavaScript console for errors

---

## ⚠️ Known Limitations

### Current Implementation Status

**✅ Working Features:**
- Template CRUD (Create, Read, Update, Delete)
- Logo/background upload
- Drag-and-drop field editor
- Position saving/loading
- Test PDF generation
- Default template setting

**❌ Not Yet Implemented:**
- **Invoice form template selector** (blocking issue)
- **Quote form template selector** (blocking issue)
- **Real PDF generation with custom templates** (blocking issue)

### Critical Gap: Template Integration Missing

**The Problem:**
The template system is fully functional in isolation, but it's not connected to the invoice/quote creation workflow.

**What This Means:**
- You CAN create custom templates ✓
- You CAN test them with mock data ✓
- You CANNOT use them for real invoices yet ❌

**Required to Fix:**
1. Add `template_id` column to `invoices` table
2. Add `template_id` column to `quotes` table
3. Add template dropdown to invoice creation form
4. Add template dropdown to quote creation form
5. Update InvoiceController to use selected template
6. Update QuoteController to use selected template
7. Implement default template logic (use company default if not selected)

**Workaround:**
None available. Template integration must be completed before production use.

### Recommended Action

**For Developers:**
Complete the integration by implementing the 7 steps above. Estimated time: 2-3 hours.

**For Users:**
Wait for integration update before relying on custom templates for production invoices/quotes.

---

## Best Practices

### Template Naming

Use descriptive names:
- ✅ "Modern Blue Logo"
- ✅ "Classic Formal Invoice"
- ✅ "Quote Template - Minimal"
- ❌ "Template 1"
- ❌ "wwfsfgsaff" (test data)

### Design Guidelines

1. **Logo Placement:**
   - Top-right or top-center is standard
   - Size: 150-200px width max
   - Leave space around it

2. **Layout Balance:**
   - Left side: Company + client info
   - Right side: Invoice metadata (number, dates)
   - Center: Items table (largest area)
   - Bottom: Totals + payment terms

3. **Font Sizes:**
   - Company name: 18-24px (bold)
   - Headings: 14-16px (bold)
   - Body text: 11-12px
   - Small print: 10px

4. **Background Images:**
   - Use subtle watermarks (20-40% opacity)
   - Avoid busy patterns
   - Test readability carefully
   - Position important elements away from watermark

5. **Color Schemes:**
   - Use brand colors sparingly
   - Ensure high contrast for text
   - Black text on white/light background is safest
   - Avoid light gray text (hard to read when printed)

### Testing Checklist

Before finalizing a template:
- [ ] Test PDF generated successfully
- [ ] Logo clear and properly sized
- [ ] Background doesn't obscure text
- [ ] All fields readable
- [ ] Items table has room for 10+ items
- [ ] Totals clearly visible
- [ ] Print preview looks professional
- [ ] Test on real invoice data (once integration complete)

---

## Support

### Getting Help

1. **Documentation:** Read this guide thoroughly
2. **Logs:** Check `storage/logs/laravel.log` for errors
3. **Browser Console:** F12 → Console tab for JavaScript errors
4. **Test Route:** Use `/templates/{id}/test-pdf` to debug PDFs

### Reporting Issues

When reporting bugs, include:
- Template ID
- Steps to reproduce
- Expected vs. actual behavior
- Screenshots
- Error messages from logs/console

---

## Appendix: Technical Details

### File Storage Locations

```
storage/app/public/
  ├── logos/           # Uploaded logo images
  └── backgrounds/     # Uploaded background images
```

### Database Tables

**invoice_templates:**
- `id` - Unique identifier
- `name` - Template name
- `is_default` - Default flag (boolean)
- `logo_path` - Path to logo file (nullable)
- `background_path` - Path to background (nullable)
- `field_positions` - JSON object with all field positions
- `page_size` - Page format (A4, Letter, etc.)

### Field Positions JSON Structure

```json
{
  "company_name": {
    "x": 50,
    "y": 50,
    "width": 300,
    "height": 40,
    "fontSize": 18,
    "bold": true
  },
  "invoice_number": {
    "x": 550,
    "y": 150,
    "width": 200,
    "height": 25,
    "fontSize": 12
  },
  "items_table": {
    "x": 50,
    "y": 350,
    "width": 700,
    "height": 300
  }
  // ... all other fields
}
```

### A4 Canvas Dimensions

- **Page Size:** 210mm × 297mm
- **Pixels @ 96 DPI:** 794px × 1123px
- **Pixels @ 300 DPI:** 2480px × 3508px

### PDF Generation

**Technology:** Laravel DomPDF (barryvdh/laravel-dompdf)

**Process:**
1. Load template from database
2. Extract field positions JSON
3. Render Blade view with absolute positioning
4. Inject invoice/quote data
5. Render logo and background
6. Convert HTML to PDF
7. Stream to browser or save

---

**End of User Guide**

For technical implementation details, see `TEMPLATE_EDITOR_PLAN.md` and `TESTING_RESULTS.md`.
