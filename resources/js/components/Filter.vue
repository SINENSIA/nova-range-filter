<template>
    <FilterContainer>
        <span>{{ filter.name }}</span>
        <template #filter>
            <div class="flex flex-col gap-1">
                <div class="flex gap-2 items-center">
                    <input
                        ref="minInput"
                        class="form-control form-input form-control-bordered w-full"
                        placeholder="min"
                    />
                    <span class="text-sm text-gray-500">→</span>
                    <input
                        ref="maxInput"
                        class="form-control form-input form-control-bordered w-full"
                        placeholder="max"
                    />
                </div>
                <p v-if="hasError" class="text-sm text-red-600 mt-1">
                    {{ __("El valor mínimo no puede ser mayor que el máximo") }}
                </p>
            </div>
        </template>
    </FilterContainer>
</template>

<script>
import IMask from "imask";
import debounce from "lodash/debounce";

export default {
    name: "nova-range-filter",
    emits: ["change"],
    props: {
        resourceName: String,
        filterKey: String,
        lens: String,
        meta: Object,
        name: String,
    },
    data() {
        return {
            value: { min: null, max: null },
            masks: { min: null, max: null },
        };
    },
    computed: {
        filter() {
            return this.$store.getters[`${this.resourceName}/getFilter`](
                this.filterKey
            );
        },
        allowNegative() {
            return this.meta?.allowNegative === true;
        },
        hasError() {
            const { min, max } = this.value;
            return min !== null && max !== null && Number(min) > Number(max);
        },
    },
    watch: {
        "filter.currentValue": {
            handler(newVal) {
                const empty =
                    !newVal || (newVal.min == null && newVal.max == null);

                if (empty) {
                    this.resetFilterFields();
                } else {
                    // También puede pasar que se repoblen desde fuera
                    this.setCurrentFilterValue();
                }
            },
            immediate: true,
            deep: true,
        },
    },
    created() {
        this.debouncedHandleChange = debounce(this.handleChange, 500);
    },
    mounted() {
        const options = getIMaskOptionsFromMeta(this.meta);

        this.masks.min = IMask(this.$refs.minInput, options);
        this.masks.max = IMask(this.$refs.maxInput, options);

        this.masks.min.on("accept", () => this.onInput("min"));
        this.masks.max.on("accept", () => this.onInput("max"));

        this.setCurrentFilterValue();

        // ✅ Escuchar reset desde Nova
        Nova.$on("filter-reset", this.onFilterReset);
    },
    beforeUnmount() {
        Nova.$off("filter-reset", this.onFilterReset);
    },
    methods: {
        onFilterReset(filterKey) {
            console.log("onFilterReset", filterKey);
            if (filterKey === this.filterKey) {
                this.resetFilterFields();
            }
        },
        resetFilterFields() {
            this.value = { min: null, max: null };

            if (this.masks.min) {
                this.masks.min.value = ""; // <-- importante para forzar limpieza visual
                this.masks.min.unmaskedValue = "";
            }

            if (this.masks.max) {
                this.masks.max.value = ""; // <-- importante también aquí
                this.masks.max.unmaskedValue = "";
            }
        },
        onInput(field) {
            const mask = this.masks[field];
            const value = mask.unmaskedValue;
            this.value[field] = value ? Number(value) : null;
            if (!this.hasError) this.debouncedHandleChange();
        },
        handleChange() {
            if (this.value.min === null && this.value.max === null) return;
            this.$store.commit(`${this.resourceName}/updateFilterState`, {
                filterClass: this.filterKey,
                value: this.value,
            });
            this.$emit("change");
        },
        setCurrentFilterValue() {
            const current = this.filter.currentValue;
            if (typeof current === "object" && current !== null) {
                this.value = current;
                if (this.masks.min)
                    this.masks.min.unmaskedValue =
                        current.min?.toString() ?? "";
                if (this.masks.max)
                    this.masks.max.unmaskedValue =
                        current.max?.toString() ?? "";
            }
        },
    },
};

function getIMaskOptionsFromMeta(meta = {}) {
    const allowNegative = meta.allowNegative === true;
    const locale = meta.locale || "es-ES";
    const format = meta.format ?? Intl.NumberFormat().resolvedOptions().style;

    const formatter = new Intl.NumberFormat(locale, {
        style: format === 2 ? "currency" : "decimal", // 2 = CURRENCY
        currency: meta.unit || "EUR",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const parts = formatter.formatToParts(1234.56);
    const thousandsSeparator =
        parts.find((p) => p.type === "group")?.value || ".";
    const radix = parts.find((p) => p.type === "decimal")?.value || ",";

    return {
        mask: Number,
        scale: 2,
        signed: allowNegative,
        thousandsSeparator,
        radix,
        mapToRadix: [radix === "," ? "." : ","],
        normalizeZeros: true,
        padFractionalZeros: false,
        min: allowNegative ? undefined : 0,
    };
}
</script>
