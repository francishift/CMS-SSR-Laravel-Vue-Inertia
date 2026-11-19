<script setup>
/**
 * Lienzo de Previsualización Aislado en Iframe para el Page Builder.
 *
 * Se ejecuta en un documento HTML completamente independiente:
 *  - Scroll nativo del navegador (cero cortes en cabeceras sticky, fondos fijos y cápsulas flotantes).
 *  - Breakpoints reales de Tailwind (sm:, md:, lg:, xl:) al redimensionar el iframe.
 *  - Cero fugas o interferencias de estilos CSS del panel de administración.
 *  - Sincronización reactiva bidireccional instantánea (< 1ms) con el editor padre mediante postMessage.
 */
import { ref, computed, onMounted, onUnmounted, nextTick, inject, watch, provide } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Frontend/Layouts/PublicLayout.vue';
import PageBuilderRenderer from '@/Frontend/Builder/PageBuilderRenderer.vue';
import DownloadDetailAside from '@/Pages/Public/Downloads/Partials/DownloadDetailAside.vue';
import { initScrollAnimations, replayAnimation, desplazarSuaveConOffset } from '@/Composables/useScrollAnimation';
import { COOKIES_BUTTON_HTML } from '@/Composables/useSanitize';

defineOptions({ layout: PublicLayout });

const page = usePage();

const props = defineProps({
    pagina: { type: Object, required: true },
    initialLayout: { type: Object, default: () => ({ sections: [] }) },
    esPlantilla: { type: Boolean, default: false },
    tipoPlantilla: { type: String, default: '' },
    categoriaMuestra: { type: Object, default: null },
    articuloMuestra: { type: Object, default: null },
    articulosMuestra: { type: Array, default: () => [] },
    descargaMuestra: { type: Object, default: null },
    descargasMuestra: { type: Array, default: () => [] },
    catalogoDescargas: { type: Object, default: null },
    categorias: { type: Array, default: () => [] },
});

provide('isBuilderPreview', true);
provide('tipoPlantilla', computed(() => props.tipoPlantilla || ''));
provide('esPlantilla', computed(() => Boolean(props.tipoPlantilla || props.esPlantilla)));

const activeCategoriaMuestra = ref(props.categoriaMuestra || null);
const activeArticuloMuestra = ref(props.articuloMuestra || null);
const activeArticulosMuestra = ref(props.articulosMuestra || []);
const activeDescargaMuestra = ref(props.descargaMuestra || props.descargasMuestra?.[0] || null);
const activeDescargasMuestra = ref(props.descargasMuestra || []);
const activePaginationPage = ref(1);
const latestRawLayout = ref(props.initialLayout || props.pagina?.layout_json || { sections: [] });
let lastLayoutJson = '';

const handlePaginationChange = (e) => {
    if (e.detail?.page !== undefined) {
        activePaginationPage.value = e.detail.page;
        if (props.tipoPlantilla === 'single_article') {
            const cat = activeCategoriaMuestra.value || props.categoriaMuestra || props.articuloMuestra?.category || {};
            const sampleArticles = (activeArticulosMuestra.value?.length ? activeArticulosMuestra.value : props.articulosMuestra) || [];
            const catArticles = sampleArticles.filter(a => {
                if (cat?.id) return String(a.category?.id || a.category_id) === String(cat.id);
                return true;
            });
            const pool = catArticles.length > 1 ? catArticles : sampleArticles;
            const targetIdx = e.detail.page - 1;
            if (pool[targetIdx]) {
                activeArticuloMuestra.value = pool[targetIdx];
            }
        }
        currentLayout.value = resolveDynamicTokensClient(latestRawLayout.value);
        lastLayoutJson = JSON.stringify(currentLayout.value);
        renderVersion.value++;

        nextTick(() => {
            initScrollAnimations();
            const gridEl = document.querySelector('.builder-element-grid-container, .builder-element-grid, [data-builder-id*="grid"], main');
            desplazarSuaveConOffset(gridEl);
        });
    }
};

function resolveDynamicTokensClient(layoutRaw) {
    if (!layoutRaw || !layoutRaw.sections || !Array.isArray(layoutRaw.sections)) return layoutRaw;

    const art = activeArticuloMuestra.value || props.articuloMuestra || (props.articulosMuestra?.length ? props.articulosMuestra[0] : {});
    const cat = activeCategoriaMuestra.value || props.categoriaMuestra || art.category || (props.categorias?.length ? props.categorias[0] : {});
    const sampleArticles = (activeArticulosMuestra.value?.length ? activeArticulosMuestra.value : props.articulosMuestra) || [];

    const catName = cat.name || art.category?.name || '';
    const catDesc = cat.description || '';
    const catSlug = cat.slug || art.category?.slug || '';
    const catImg = cat.featured_image || '';
    const catIcon = cat.icon || '';
    const catColor = cat.color || '';

    const artTitle = art.title || '';
    const artExcerpt = art.excerpt || '';
    const artBody = art.body || '';
    const artAuthor = art.author?.name || '';
    const artDate = art.published_at || '';
    const artReading = String(art.reading_time || '1');
    const artImg = art.featured_image || '';
    const artIcon = art.icon || '';
    const artUrl = art.slug ? `/${art.category?.slug || catSlug || 'articulos'}/${art.slug}` : '#';

    const diccionarioNormalizado = {
        '{category.name}': catName,
        '{category.title}': catName,
        '{category.description}': catDesc,
        '{category.featured_image}': catImg,
        '{category.image}': catImg,
        '{category.slug}': catSlug,
        '{category.icon}': catIcon,
        '{category.color}': catColor,
        '{category.url}': `/${catSlug}`,
        '{article.title}': artTitle,
        '{article.name}': artTitle,
        '{article.subtitle}': artExcerpt,
        '{article.excerpt}': artExcerpt,
        '{article.body}': artBody,
        '{article.content}': artBody,
        '{article.author.name}': artAuthor,
        '{article.author}': artAuthor,
        '{article.published_at}': artDate,
        '{article.date}': artDate,
        '{article.reading_time}': artReading,
        '{article.featured_image}': artImg,
        '{article.image}': artImg,
        '{article.icon}': artIcon,
        '{article.category.name}': art.category?.name || catName,
        '{article.category}': art.category?.name || catName,
        '{article.category.url}': `/${art.category?.slug || catSlug}`,
        '{article.category.slug}': art.category?.slug || catSlug,
        '{article.url}': artUrl,
        '{site.name}': page.props.siteSettings?.site_name || '',
        '{site.title}': page.props.siteSettings?.site_name || '',
        '{site.description}': page.props.siteSettings?.site_description || '',
    };

    // Mapear tokens dinámicos `{download.*}` cuando se previsualiza una ficha de descarga
    const dl = activeDescargaMuestra.value || props.descargaMuestra || (activeDescargasMuestra.value?.[0] ?? props.descargasMuestra?.[0] ?? null);
    if (dl) {
        const catTitle = props.catalogoDescargas?.page_title || 'Descargas';
        const catUrl = props.catalogoDescargas?.public_url || '/descargas';
        const dlChangelogHtml = dl.changelog
            ? String(dl.changelog).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>')
            : '';
        const escCatTitle = String(catTitle).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const dlBreadcrumbHtml = `<nav aria-label="Breadcrumb" class="text-sm text-zinc-500 dark:text-zinc-400"><a href="/" class="hover:text-[var(--primary-color,#10b981)] transition-colors no-underline text-inherit">Inicio</a><span class="mx-2 opacity-60">/</span><a href="${catUrl}" class="hover:text-[var(--primary-color,#10b981)] transition-colors no-underline text-inherit font-medium">${escCatTitle}</a></nav>`;

        Object.assign(diccionarioNormalizado, {
            '{download.title}': dl.title || '',
            '{download.subtitle}': dl.subtitle || '',
            '{download.short_description}': dl.short_description || '',
            '{download.excerpt}': dl.short_description || '',
            '{download.body}': dl.body || '',
            '{download.content}': dl.body || '',
            '{download.featured_image}': dl.featured_image || '',
            '{download.image}': dl.featured_image || '',
            '{download.icon}': dl.icon || 'pi pi-box',
            '{download.version}': dl.version || '',
            '{download.compatibility}': dl.compatibility || '',
            '{download.changelog}': dlChangelogHtml,
            '{download.price}': dl.formatted_price || 'Gratis',
            '{download.file_size}': dl.formatted_file_size || '',
            '{download.license}': dl.license_type || '',
            '{download.demo_url}': dl.demo_url || '',
            '{download.documentation_url}': dl.documentation_url || '',
            '{download.url}': dl.url || '#',
            '{download.download_url}': dl.download_request_url || '#',
            '{download.catalog_title}': catTitle,
            '{download.catalog_url}': catUrl,
            '{download.breadcrumb}': dlBreadcrumbHtml,
        });
    }

    // Mapear campos extra dinámicos
    if (art.extra_fields_data && typeof art.extra_fields_data === 'object') {
        for (const [key, val] of Object.entries(art.extra_fields_data)) {
            if (val !== null && val !== undefined) {
                const kLower = key.toLowerCase();
                diccionarioNormalizado[`{article.extra_fields.${kLower}}`] = String(val);
                diccionarioNormalizado[`{extra.${kLower}}`] = String(val);
                diccionarioNormalizado[`{article.${kLower}}`] = String(val);
            }
        }
    }

    try {
        const cloned = JSON.parse(JSON.stringify(layoutRaw));

        // 1. Inyectar resolved_data en Cuadrículas de Artículos (Grid), Paginación y Galerías de forma recursiva
        const resolverElementos = (elements) => {
            for (const elem of elements || []) {
                if (elem.type === 'grid_articles' || elem.type === 'grid') {
                    const binding = elem.content?.dynamic_binding || {};

                    if (binding.source === 'downloads') {
                        const rawLimit = Number(binding.limit || elem.content?.limit || 12);
                        const limit = (rawLimit > 0 && rawLimit < 999) ? rawLimit : 12;
                        let poolDescargas = [...((activeDescargasMuestra.value?.length ? activeDescargasMuestra.value : props.descargasMuestra) || [])];
                        if (props.tipoPlantilla === 'single_download' && dl?.id) {
                            poolDescargas = poolDescargas.filter(item => String(item.id) !== String(dl.id));
                        }
                        const orderBy = binding.order_by || elem.content?.order_by || 'order_asc';
                        if (orderBy === 'title_asc') {
                            poolDescargas.sort((a, b) => (a.title || '').localeCompare(b.title || ''));
                        } else if (orderBy === 'title_desc') {
                            poolDescargas.sort((a, b) => (b.title || '').localeCompare(a.title || ''));
                        } else if (orderBy === 'published_at_desc') {
                            poolDescargas.sort((a, b) => (b.published_at_iso || '').localeCompare(a.published_at_iso || ''));
                        } else if (orderBy === 'published_at_asc') {
                            poolDescargas.sort((a, b) => (a.published_at_iso || '').localeCompare(b.published_at_iso || ''));
                        } else {
                            poolDescargas.sort((a, b) => (Number(a.order) || 0) - (Number(b.order) || 0));
                        }
                        elem.resolved_data = poolDescargas.slice(0, limit);
                        continue;
                    }

                    const catIdFilter = binding.category_id || elem.content?.category_id;

                    let articlesForGrid = [];
                    if (catIdFilter && catIdFilter !== 'all' && catIdFilter !== 'current') {
                        articlesForGrid = sampleArticles.filter(a => String(a.category?.id || a.category_id) === String(catIdFilter));
                    } else if (props.esPlantilla && cat?.id) {
                        articlesForGrid = sampleArticles.filter(a => String(a.category?.id || a.category_id) === String(cat.id));
                    } else {
                        articlesForGrid = sampleArticles;
                    }

                    if (!articlesForGrid.length && sampleArticles.length) {
                        articlesForGrid = sampleArticles;
                    }

                    const rawLimit = Number(binding.limit || elem.content?.limit || 0);
                    const limit = (rawLimit > 0 && rawLimit < 999) ? rawLimit : Number(cat?.per_page || 12);
                    const pageNum = activePaginationPage.value || 1;
                    const start = (pageNum - 1) * limit;

                    let pagedSlice = [];
                    if (articlesForGrid.length > start) {
                        pagedSlice = articlesForGrid.slice(start, start + limit);
                    } else if (articlesForGrid.length > 0) {
                        const offset = start % articlesForGrid.length;
                        pagedSlice = articlesForGrid.slice(offset, offset + limit);
                        if (pagedSlice.length < limit && articlesForGrid.length >= limit) {
                            pagedSlice = pagedSlice.concat(articlesForGrid.slice(0, limit - pagedSlice.length));
                        }
                    }

                    elem.resolved_data = pagedSlice.length ? pagedSlice : articlesForGrid.slice(0, limit);
                }

                if (elem.type === 'pagination') {
                    const pagMode = elem.content?.mode || 'article_nav';

                    if (pagMode === 'article_nav') {
                        const currentArtId = art?.id;
                        let prev = null;
                        let next = null;

                        const catArticles = sampleArticles.filter(a => {
                            if (cat?.id) return String(a.category?.id || a.category_id) === String(cat.id);
                            return true;
                        });
                        const pool = catArticles.length > 1 ? catArticles : sampleArticles;

                        const currentIdx = pool.findIndex(a => a.id === currentArtId || a.slug === art?.slug);
                        if (currentIdx !== -1) {
                            if (currentIdx > 0) prev = pool[currentIdx - 1];
                            if (currentIdx < pool.length - 1) next = pool[currentIdx + 1];
                        } else if (pool.length > 1) {
                            prev = pool[0];
                            next = pool[1];
                        } else if (pool.length === 1) {
                            prev = pool[0];
                        }

                        const mapNav = (a) => a ? {
                            id: a.id,
                            title: a.title,
                            slug: a.slug,
                            url: a.slug ? (a.category?.slug ? `/${a.category.slug}/${a.slug}` : `/articulos/${a.slug}`) : '#',
                            featured_image: a.featured_image || '',
                            category: a.category ? { name: a.category.name, slug: a.category.slug } : null,
                        } : null;

                        elem.resolved_data = {
                            navegacion: {
                                anterior: mapNav(prev),
                                siguiente: mapNav(next),
                            }
                        };
                    } else if (pagMode === 'archive_numeric') {
                        if (props.tipoPlantilla === 'single_article') {
                            const catArticles = sampleArticles.filter(a => {
                                if (cat?.id) return String(a.category?.id || a.category_id) === String(cat.id);
                                return true;
                            });
                            const pool = catArticles.length > 1 ? catArticles : sampleArticles;
                            const total = pool.length || 7;
                            const currentArtId = art?.id;
                            let currentIdx = pool.findIndex(a => a.id === currentArtId || a.slug === art?.slug);
                            if (currentIdx === -1) currentIdx = (activePaginationPage.value ? activePaginationPage.value - 1 : 0);
                            currentIdx = Math.max(0, Math.min(total - 1, currentIdx));
                            const currentPage = currentIdx + 1;

                            const catSlug = cat?.slug || art?.category?.slug || 'categoria';
                            const prevArt = currentIdx > 0 ? pool[currentIdx - 1] : null;
                            const nextArt = currentIdx < total - 1 ? pool[currentIdx + 1] : null;

                            const links = [
                                {
                                    url: prevArt ? `#page-${currentPage - 1}` : null,
                                    label: '&laquo; Anterior',
                                    active: false,
                                    page: prevArt ? currentPage - 1 : null,
                                },
                            ];
                            for (let p = 1; p <= total; p++) {
                                links.push({
                                    url: `#page-${p}`,
                                    label: String(p),
                                    active: p === currentPage,
                                    page: p,
                                });
                            }
                            links.push({
                                url: nextArt ? `#page-${currentPage + 1}` : null,
                                label: 'Siguiente &raquo;',
                                active: false,
                                page: nextArt ? currentPage + 1 : null,
                            });

                            elem.resolved_data = {
                                paginacion: {
                                    links,
                                    current_page: currentPage,
                                    last_page: total,
                                    total,
                                    base_slug: catSlug,
                                    is_article_series: true,
                                }
                            };
                        } else {
                            const rawLimit = Number(cat?.per_page || 0);
                            const perPage = (rawLimit > 0 && rawLimit < 999) ? rawLimit : 12;
                            const total = Math.max(sampleArticles.length || 18, 1);
                            const lastPage = Math.max(1, Math.ceil(total / perPage));
                            const currentPage = Math.min(lastPage, activePaginationPage.value || 1);

                            const links = [
                                {
                                    url: currentPage > 1 ? `#page-${currentPage - 1}` : null,
                                    label: '&laquo; Anterior',
                                    active: false,
                                    page: Math.max(1, currentPage - 1)
                                },
                            ];
                            for (let p = 1; p <= lastPage; p++) {
                                links.push({
                                    url: `#page-${p}`,
                                    label: String(p),
                                    active: p === currentPage,
                                    page: p,
                                });
                            }
                            links.push({
                                url: currentPage < lastPage ? `#page-${currentPage + 1}` : null,
                                label: 'Siguiente &raquo;',
                                active: false,
                                page: Math.min(lastPage, currentPage + 1)
                            });

                            elem.resolved_data = {
                                paginacion: {
                                    links,
                                    current_page: currentPage,
                                    last_page: lastPage,
                                    total,
                                    base_slug: cat?.slug || '',
                                }
                            };
                        }
                    }
                }

                // Galería dinámica
                if (elem.type === 'gallery' && elem.content?.mode === 'dynamic') {
                    const source = elem.content?.dynamic_binding?.source || 'current_article';

                    if (source === 'current_article' && art) {
                        const selectedFields = elem.content?.dynamic_binding?.selected_image_fields || [];
                        const extraData = art.extra_fields_data || {};
                        const galleryItems = [];

                        for (const key of selectedFields) {
                            const src = extraData[key];
                            if (src && typeof src === 'string' && src.trim()) {
                                galleryItems.push({
                                    src,
                                    alt: art.title || '',
                                    title: '',
                                    caption: '',
                                    tags: '',
                                    type: 'image',
                                });
                            }
                        }
                        elem.resolved_data = galleryItems;
                    } else if (source === 'articles' || source === 'current_category') {
                        const galBinding = elem.content?.dynamic_binding || {};
                        const galCatId = galBinding.category_id;
                        let galArticles;

                        if (galCatId && galCatId !== 'all' && galCatId !== 'current') {
                            galArticles = sampleArticles.filter(a => String(a.category?.id || a.category_id) === String(galCatId));
                        } else if (galCatId === 'current' && cat?.id) {
                            galArticles = sampleArticles.filter(a => String(a.category?.id || a.category_id) === String(cat.id));
                        } else {
                            galArticles = sampleArticles;
                        }
                        if (!galArticles.length) galArticles = sampleArticles;

                        const galLimit = Number(galBinding.limit || 12);
                        const mapping = elem.content?.dynamic_mapping || {};
                        const srcField = mapping.src_field || 'featured_image';
                        const titleField = mapping.title_field || 'title';
                        const captionField = mapping.caption_field || 'none';
                        const altField = mapping.alt_field || 'title';

                        const resolveField = (article, field) => {
                            if (!field || field === 'none') return '';
                            if (field.startsWith('extra_fields_data.')) {
                                const key = field.replace('extra_fields_data.', '');
                                return article.extra_fields_data?.[key] || '';
                            }
                            if (field === 'category.name') return article.category?.name || '';
                            return article[field] || '';
                        };

                        const galItems = galArticles.slice(0, galLimit).map(a => ({
                            src: resolveField(a, srcField),
                            title: resolveField(a, titleField),
                            caption: resolveField(a, captionField),
                            alt: resolveField(a, altField),
                            tags: '',
                            type: 'image',
                        })).filter(i => i.src);

                        elem.resolved_data = galItems;
                    }
                }

                // Soporte recursivo para sublayouts
                if (elem.type === 'sublayout' && elem.content?.rows && Array.isArray(elem.content.rows)) {
                    for (const sr of elem.content.rows) {
                        for (const sc of sr.columns || []) {
                            resolverElementos(sc.elements || []);
                        }
                    }
                }
            }
        };

        for (const sec of cloned.sections || []) {
            for (const row of sec.rows || []) {
                for (const col of row.columns || []) {
                    resolverElementos(col.elements || []);
                }
            }
        }

        // 2. Sustituir variables de texto dinámicas (totalmente insensible a mayúsculas/minúsculas)
        function sustituirString(str) {
            if (typeof str !== 'string' || !str) return str;
            const conCookies = str.replace(/\{\{\s*cookies_button\s*\}\}/gi, COOKIES_BUTTON_HTML);
            return conCookies.replace(/\{([a-zA-Z0-9_.\-]+)\}/gi, (match) => {
                const clave = match.toLowerCase();
                return Object.prototype.hasOwnProperty.call(diccionarioNormalizado, clave)
                    ? diccionarioNormalizado[clave]
                    : match;
            });
        }

        function procesar(nodo) {
            if (!nodo || typeof nodo !== 'object') return;
            for (const k in nodo) {
                if (k === 'resolved_data') continue;
                if (typeof nodo[k] === 'string') {
                    nodo[k] = sustituirString(nodo[k]);
                } else if (typeof nodo[k] === 'object') {
                    procesar(nodo[k]);
                }
            }
        }

        procesar(cloned);
        return cloned;
    } catch (e) {
        return layoutRaw;
    }
}

const currentLayout = ref(resolveDynamicTokensClient(props.initialLayout || props.pagina?.layout_json || { sections: [] }));
const renderVersion = ref(0);
lastLayoutJson = JSON.stringify(currentLayout.value);

const setTransparentHeaderOverride = inject('setTransparentHeaderOverride', null);

// Sincronizar en vivo el modo de cabecera transparente en el lienzo de previsualización
watch(
    () => currentLayout.value?.sections?.[0]?.settings,
    (s) => {
        if (setTransparentHeaderOverride) {
            setTransparentHeaderOverride(
                s?.transparent_header
                    ? { active: true, pull: Boolean(s.transparent_header_pull), navColor: s.transparent_header_nav_color || 'inherit' }
                    : { active: false, pull: false, navColor: 'inherit' }
            );
        }
    },
    { immediate: true, deep: true }
);

// Indicador visual de selección en el lienzo
const selectedElementId = ref(null);

const handleParentMessage = (event) => {
    if (!event.data || typeof event.data !== 'object') return;

    switch (event.data.type) {
        case 'CMS_BUILDER_UPDATE_LAYOUT':
            let sampleChanged = false;
            if (event.data.categoriaMuestra) {
                activeCategoriaMuestra.value = event.data.categoriaMuestra;
                sampleChanged = true;
            }
            if (event.data.articuloMuestra) {
                activeArticuloMuestra.value = event.data.articuloMuestra;
                sampleChanged = true;
            }
            if (event.data.articulosMuestra && Array.isArray(event.data.articulosMuestra)) {
                activeArticulosMuestra.value = event.data.articulosMuestra;
                sampleChanged = true;
            }
            if (event.data.descargaMuestra) {
                activeDescargaMuestra.value = event.data.descargaMuestra;
                sampleChanged = true;
            }
            if (event.data.descargasMuestra && Array.isArray(event.data.descargasMuestra) && event.data.descargasMuestra.length) {
                activeDescargasMuestra.value = event.data.descargasMuestra;
            }
            if (event.data.layout) {
                latestRawLayout.value = event.data.layout;
                const resolved = resolveDynamicTokensClient(event.data.layout);
                const resolvedJson = JSON.stringify(resolved);
                // Solo forzar re-render si el layout o datos de muestra realmente han cambiado
                if (resolvedJson !== lastLayoutJson || sampleChanged) {
                    lastLayoutJson = resolvedJson;
                    currentLayout.value = resolved;
                    renderVersion.value++;
                }
            }
            if (event.data.selectedId !== undefined) {
                selectedElementId.value = event.data.selectedId;
            }
            nextTick(() => {
                initScrollAnimations();
                if (sampleChanged) {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
            break;

        case 'CMS_BUILDER_REPLAY_ANIMATION':
            nextTick(() => {
                const targetId = event.data.targetId;
                if (targetId) {
                    const el = document.querySelector(`[data-builder-id="${targetId}"]`);
                    if (el) {
                        const animChildren = el.querySelectorAll('[data-cms-animate]');
                        if (animChildren.length > 0) {
                            animChildren.forEach((child) => replayAnimation(child));
                        }
                        if (el.hasAttribute('data-cms-animate')) {
                            replayAnimation(el);
                        }
                        return;
                    }
                }
                // Si no hay elemento específico, reproducir los visibles
                document.querySelectorAll('[data-cms-animate]').forEach((el) => {
                    replayAnimation(el);
                });
            });
            break;

        case 'CMS_BUILDER_SCROLL_TO':
            if (event.data.id) {
                nextTick(() => {
                    const el = document.querySelector(`[data-builder-id="${event.data.id}"]`);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            }
            break;
    }
};

// Interceptar clics en el lienzo para navegar a enlaces o seleccionar elementos en el maquetador
const handleDocumentClick = (e) => {
    // 0. Si el clic fue en un botón de shortcode de cookies
    const ccBtn = e.target.closest('[data-cc-shortcode]');
    if (ccBtn) {
        e.preventDefault();
        e.stopPropagation();
        if (window.parent && window.parent.CookieConsent) {
            try { window.parent.CookieConsent.showPreferences(); return; } catch {}
        }
        if (window.CookieConsent) {
            try { window.CookieConsent.showPreferences(); return; } catch {}
        }
        return;
    }

    // 1. Si el clic fue en un enlace <a> o en una tarjeta/botón con URL de navegación
    const link = e.target.closest('a') || e.target.closest('[data-builder-navigate-url]');
    if (link) {
        const href = link.getAttribute('href') || link.getAttribute('data-builder-navigate-url');
        if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
            e.preventDefault();
            e.stopPropagation();

            // Si el enlace pertenece a un bloque del Builder aún NO seleccionado (y no es un botón explícito de acción),
            // seleccionar primero el bloque en el Inspector para poder editar sus ajustes sin salir accidentalmente de la página.
            const parentBuilderBlock = link.closest('[data-builder-id]');
            const isDirectActionBtn = link.hasAttribute('data-builder-nav-direct');
            if (parentBuilderBlock && !isDirectActionBtn) {
                const blockId = parentBuilderBlock.getAttribute('data-builder-id');
                if (blockId && selectedElementId.value !== blockId && window.parent && window.parent !== window) {
                    selectedElementId.value = blockId;
                    window.parent.postMessage({
                        type: 'CMS_BUILDER_SELECT_ELEMENT',
                        id: blockId,
                    }, '*');
                    return;
                }
            }

            if (window.parent && window.parent !== window) {
                window.parent.postMessage({
                    type: 'CMS_BUILDER_NAVIGATE_TO_URL',
                    url: href,
                }, '*');
            }
            return;
        }
    }

    // 2. Si se hizo clic en un elemento estructural o modular del lienzo (secciones, filas, columnas, elementos)
    const targetWithId = e.target.closest('[data-builder-id]');
    if (targetWithId) {
        if (window.parent && window.parent !== window) {
            const id = targetWithId.getAttribute('data-builder-id');
            window.parent.postMessage({
                type: 'CMS_BUILDER_SELECT_ELEMENT',
                id: id,
            }, '*');
        }

        // Si el clic es sobre un elemento interactivo (imágenes de galería con lightbox, botones, acordeones, inputs),
        // no prevenimos el comportamiento ni detenemos la propagación para que el componente hijo ejecute su acción.
        const isInteractiveClick = e.target.closest('button, [role="button"], figure, .cursor-pointer, input, select, textarea');
        if (!isInteractiveClick) {
            e.preventDefault();
        }
        return;
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('message', handleParentMessage);
        window.addEventListener('cms-builder-pagination-change', handlePaginationChange);
        document.addEventListener('click', handleDocumentClick, true);

        // Notificar al editor padre que el iframe está montado y listo
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({ type: 'CMS_BUILDER_FRAME_READY' }, '*');
        }

        nextTick(() => {
            initScrollAnimations();
        });
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('message', handleParentMessage);
        window.removeEventListener('cms-builder-pagination-change', handlePaginationChange);
        document.removeEventListener('click', handleDocumentClick, true);
    }
});
</script>

<template>
    <div class="builder-preview-frame-wrapper min-h-screen">
        <!-- Vista realista de 2 columnas + Ruta del sitio + Secciones inferiores para la Plantilla de Ficha Individual de Descarga -->
        <template v-if="tipoPlantilla === 'single_download'">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
                <nav
                    v-if="catalogoDescargas?.show_breadcrumb !== false"
                    aria-label="Breadcrumb"
                    class="mb-6 text-sm text-zinc-500 dark:text-zinc-400"
                >
                    <a href="/" data-builder-nav-direct="true" class="hover:text-[var(--primary-color,#10b981)] transition-colors no-underline text-inherit">
                        Inicio
                    </a>
                    <span class="mx-2 opacity-60">/</span>
                    <a
                        :href="catalogoDescargas?.public_url || '/descargas'"
                        data-builder-nav-direct="true"
                        class="hover:text-[var(--primary-color,#10b981)] transition-colors no-underline font-medium text-zinc-700 dark:text-zinc-200"
                    >
                        {{ catalogoDescargas?.page_title || 'Descargas' }}
                    </a>
                </nav>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
                    <div class="lg:col-span-8 min-w-0">
                        <PageBuilderRenderer
                            :layout="{ ...currentLayout, sections: (currentLayout?.sections || []).slice(0, 1) }"
                            :key="`dl-preview-main-${renderVersion}`"
                        />
                    </div>
                    <DownloadDetailAside
                        v-if="activeDescargaMuestra"
                        :descarga="activeDescargaMuestra"
                    />
                </div>
            </div>

            <PageBuilderRenderer
                v-if="(currentLayout?.sections || []).length > 1"
                :layout="{ ...currentLayout, sections: (currentLayout?.sections || []).slice(1) }"
                :index-offset="1"
                :key="`dl-preview-bottom-${renderVersion}`"
            />
        </template>

        <PageBuilderRenderer v-else :layout="currentLayout" :key="renderVersion" />
    </div>
</template>

<style>
/* Estilos auxiliares para el cursor de edición en el iframe */
[data-builder-id] {
    cursor: default;
}
</style>
