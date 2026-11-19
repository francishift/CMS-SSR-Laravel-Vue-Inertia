/**
 * Composable Reutilizable para Animaciones de Elementos Modulares del Page Builder.
 *
 * Calcula el tipo de animación, hereda y apila automáticamente el retardo
 * de la sección/columna padre, e inyecta la variable CSS `--cms-anim-delay` inline
 * para garantizar un retardo 100% exacto y fiable sin carreras de renderizado.
 */
import { computed, inject, ref } from 'vue';

export function useElementAnimation(props) {
    const parentAnimationDelay = inject('parentAnimationDelay', ref(0));

    const animationName = computed(() => {
        const a = props.content?.animation;
        return (a && a !== 'none') ? a : 'none';
    });

    const ownDelay = computed(() => {
        const d = props.content?.animation_delay;
        return (d !== undefined && d !== null && !isNaN(Number(d))) ? Number(d) : 0;
    });

    // Retardo acumulado total (retardo de la sección/columna padre + retardo propio del elemento)
    const totalDelay = computed(() => {
        if (animationName.value === 'none') return 0;
        return (parentAnimationDelay.value || 0) + ownDelay.value;
    });

    const animAttrs = computed(() => {
        if (animationName.value === 'none') return {};
        return {
            'data-cms-animate': animationName.value,
            'data-cms-delay': String(totalDelay.value),
        };
    });

    const animStyle = computed(() => {
        if (animationName.value === 'none') return {};
        return { '--cms-anim-delay': `${totalDelay.value}ms` };
    });

    return {
        animationName,
        totalDelay,
        animAttrs,
        animStyle,
    };
}
