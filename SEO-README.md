# Meta Data and Page Description Update Guide

This guide provides instructions on how to update meta data and page descriptions for size-specific and category-specific pages on the Yardsign Plus platform.

## 1. Updating Meta Data for Size Pages

For updating the meta data such as title, description, keywords, and header tags on size-specific pages (e.g., 24x24 or 24x18 yard signs):

**Example URLs:**
- [24x24 Wedding Yard Signs](https://www.yardsignplus.com/wedding/24x24-yard-signs)
- [24x18 Wedding Yard Signs](https://www.yardsignplus.com/wedding/24x18-yard-signs)

You need to update the meta data in the following file:
- **File Path:** `src/Constant/MetaData/SizeLandingPage.php`

## 2. Updating Meta Data for Category Pages

To update the meta data for category pages such as title, description, keywords, header tags, and content:

**Example URLs:**
- [Contractor Yard Signs](https://www.yardsignplus.com/contractor)
- [Wedding Yard Signs](https://www.yardsignplus.com/wedding)

You can make these updates via the **Admin Panel** by navigating to the respective category page under the category menu.

## 3. Updating Page Description for Size Pages

For updating the page description for size-specific pages:

**Example URLs:**
- [24x24 Wedding Yard Signs](https://www.yardsignplus.com/wedding/24x24-yard-signs)
- [24x18 Wedding Yard Signs](https://www.yardsignplus.com/wedding/24x18-yard-signs)

You need to create or update the size file in the respective directory:
- **File Path:** `templates/common/category-description/wedding`

## 4. Updating Content for Editor Page Description

For updating the content in the editor for size-specific pages:

**Example URLs:**
- [Custom 9x12 Yard Signs](https://www.yardsignplus.com/shop/custom-9x12-yard-sign)
- [Wedding Yard Sign (WE0353)](https://www.yardsignplus.com/wedding/shop/yard-sign/WE0353)

You need to create or update the file in the respective directory:
- **File Path:** `templates/common/production-description/wedding`

If no specific file exists, the content will default to:
- **File Path:** `templates/common/production-description/default.html.twig`
