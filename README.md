# Category Banner

Manage banners and attach them to category pages, from the Thelia back office.

The banners themselves (title, description, URL, button label, image) are created and
edited from the module configuration page. Each banner can then be attached to one or
more categories with a position and a size, either from the category edit page or,
on the front office, injected into the product loop of a category.

This module targets **Thelia 3** and renders its back office with the **Twig**
`default-twig` template. The legacy Smarty templates (`templates/backOffice/default/`)
are kept for Thelia 2 compatibility.

## Installation

### Manually

* Copy the module into `<thelia_root>/local/modules/` directory and be sure that the name of the module is `CategoryBanner`.
* Activate it in your Thelia administration panel.

### Composer

Add it in your main Thelia `composer.json` file

```
composer require thelia/category-banner-module
```

## Usage

1. Go to **Modules → Category Banner** (or the *Manage banners* entry in the tools menu).
2. Create a banner: title, description, URL, button label and image.
3. Open a category (**Catalog → edit a category**) and use the **Banner** tab to attach
   one or more banners, each with a position and a size.
4. On the front office, enable banners on a product loop with `enable_banners="true"`
   (see the loops below) to insert the category banners among the products.

## Hooks

Hooks are auto-discovered in Thelia 3 via `CategoryBanner\Hook\BackHook::getSubscribedHooks()`
(no `config.xml` declaration needed).

| Hook | Type | Purpose |
|--- |--- |--- |
| `main.top-menu-tools` | back | Adds a *Manage banners* entry in the tools menu |
| `module.configuration` | back | Banner list + create/delete on the module config page |
| `module.config-js` | back | Behaviour (delete confirmation) of the config page |
| `category.tab` | back | Adds the **Banner** tab on the category edit page |

## Loops

### banner_loop

Lists banners.

#### Input arguments

| Argument | Description |
|--- |--- |
| **id** | Restrict to a banner id. Example: `id="3"`. |
| **width** | Target image width in pixels. Example: `width="580"`. |
| **height** | Target image height in pixels. Example: `height="300"`. |
| **resize_mode** | Image resize mode: `crop`, `borders` or `none` (default `none`). |
| **lang_id** | Locale used for the translatable fields. Example: `lang_id="1"`. |

#### Output arguments

| Variable | Description |
|--- |--- |
| `$ID` | Banner id |
| `$TITLE` | Banner title |
| `$DESCRIPTION` | Banner description |
| `$URL` | Banner link URL |
| `$BUTTON_LABEL` | Banner button label |
| `$IMAGE_URL` | Processed (resized) image URL |
| `$ORIGINAL_IMAGE_URL` | Original image URL |
| `$IMAGE_PATH` | Processed image cache path |
| `$PROCESSING_ERROR` | `true` if the image could not be processed |
| `$IS_SVG` | `true` if the source image is an SVG |

#### Example

```smarty
{loop name="banners" type="banner_loop" width="580" resize_mode="crop"}
    <a href="{$URL}"><img src="{$IMAGE_URL nofilter}" alt="{$TITLE}"></a>
{/loop}
```

### category_banner_loop

Lists the banners attached to a category (associations).

#### Input arguments

| Argument | Description |
|--- |--- |
| **category_banner_id** | Restrict to an association id. |
| **banner_id** | Restrict to a banner id. |
| **category_id** | Restrict to a category id. Example: `category_id="12"`. |
| **width** | Target image width in pixels. |
| **height** | Target image height in pixels. |
| **resize_mode** | Image resize mode: `crop`, `borders` or `none` (default `none`). |
| **lang_id** | Locale used for the translatable fields. |

#### Output arguments

| Variable | Description |
|--- |--- |
| `$ID` | Association id |
| `$BANNER_ID` | Attached banner id |
| `$CATEGORY_ID` | Category id |
| `$BANNER_TITLE` | Banner title |
| `$BANNER_DESCRIPTION` | Banner description |
| `$BANNER_URL` | Banner link URL |
| `$BANNER_BUTTON_LABEL` | Banner button label |
| `$BANNER_POSITION` | Position of the banner within the category |
| `$BANNER_SIZE` | Display size of the banner (1 or 2) |
| `$IMAGE_URL` | Processed (resized) image URL |
| `$PROCESSING_ERROR` | `true` if the image could not be processed |
| `$IS_SVG` | `true` if the source image is an SVG |

#### Example

```smarty
{loop name="cat_banners" type="category_banner_loop" category_id="12" width="580"}
    <div class="banner banner--size-{$BANNER_SIZE}">
        <a href="{$BANNER_URL}"><img src="{$IMAGE_URL nofilter}" alt="{$BANNER_TITLE}"></a>
    </div>
{/loop}
```

## Product loop extension

`CategoryBanner\EventListeners\ProductLoopListener` adds an `enable_banners` argument to
the native `product` loop. When set to `true` on a category listing, the category banners
are inserted among the products at their configured positions:

| Argument | Description |
|--- |--- |
| **enable_banners** | `true` to interleave the category banners with the products (default `false`). |

Extra output variables on the product loop rows:

| Variable | Description |
|--- |--- |
| `$IS_BANNER` | `true` when the row is a banner (not a product) |
| `$BANNER_ID` | Banner id when `$IS_BANNER` is `true` |
| `$CATEGORY_BANNER_ID` | Association id when `$IS_BANNER` is `true` |

```smarty
{loop name="products" type="product" category="12" enable_banners="true"}
    {if $IS_BANNER}
        {loop name="b" type="category_banner_loop" category_banner_id="$CATEGORY_BANNER_ID"}
            <a href="{$BANNER_URL}"><img src="{$IMAGE_URL nofilter}" alt="{$BANNER_TITLE}"></a>
        {/loop}
    {else}
        {* render the product *}
    {/if}
{/loop}
```
