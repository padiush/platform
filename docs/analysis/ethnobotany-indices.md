# Quantitative ethnobotany indices — specification

This is a **scientific contract**. Researchers will publish numbers this platform
computes; a subtly wrong formula that reaches three papers is a credibility
failure that cannot be walked back. So every index here is defined with its
source, its edge cases, and a worked example that **doubles as a test fixture**
(per the AGENTS.md rule that features ship with regression tests — here the spec
*is* the test oracle).

> **Field-agnostic.** Despite the name, these indices are the standard toolkit of
> quantitative *ethnobiology*, not just botany. An ethnozoology or ethnomycology
> study computes the same math. Implementing them once serves every subfield —
> see [../decisions/0006-multi-subfield-architecture.md](../decisions/0006-multi-subfield-architecture.md).

## The unit of analysis: the use report (UR)

A **use report** is one record of: *one informant* cited *one species* for *one
use in one use-category*. It is the atom every index is built from.

Mapping to the data model ([../data-model.md](../data-model.md)):

| Concept | Comes from | Status |
|---|---|---|
| Informant *i* | one `InterviewInstance` (one interview = one informant's responses) | ✅ exists |
| Species *s* | `InstanceAnswer.catalog_species_id` (the folk-name → taxon link) | ✅ exists |
| Use-category *u* | `InterviewItem.is_use_category` ([ADR 0007](../decisions/0007-use-category-as-item-role.md)) | ✅ built |
| Informant count *N* | interviews of forms that carry a species field (`link_to_species`) | ✅ built |

> ### ✅ BUILT — how a "use" is identified
>
> A linked answer's **use-category** comes from `InterviewItem.is_use_category`
> — the field flagged as a use category at instrument-design time, mutually
> exclusive with `link_to_species`. The answer to a use-category item, within the
> same repeatable set as the linked species, supplies *u*. See
> [../decisions/0007-use-category-as-item-role.md](../decisions/0007-use-category-as-item-role.md).
>
> ✅ Built end to end: the computation in `app/Services/EthnobiologyIndices.php`
> (verified against the worked example below), surfaced as a report page
> (`data.reports` → `Data/Reports`) and an xlsx/csv export
> (`data.reports.download`), both gated on `generate_reports`.

**N (the denominator)** is *all informants surveyed with a species instrument* —
interviews of forms that carry a `link_to_species` field — not just those who
cited a given species. A species no informant mentioned contributes 0 to
UV/RFC/CI, which is correct — rarity should lower the score. A project may also
hold unrelated instruments (an interview can be recorded with no species/use
linking — e.g. a demographics form); those interviews are **excluded from N**, so
mixing instruments in one project does not dilute the indices.

---

## Indices

Notation: `N` = total informants; `s` = a species; `u` = a use-category;
`UR` = use reports.

### Relative Frequency of Citation — RFC

*Independent of use-categories; computable today.*

```
RFC(s) = FC(s) / N
```
- `FC(s)` = number of *distinct informants* who cited species `s` (count an
  informant once even if they cite it repeatedly).
- Range `0 < RFC ≤ 1`. Higher = more widely known/used species.
- **Edge cases:** `N = 0` → RFC undefined (no survey); report N/A, do not divide.
  Count each informant at most once per species.
- Source: Tardío & Pardo-de-Santayana (2008).

### Use Value — UV

*Cultural versatility of a species.*

```
UV(s) = ( Σ_i U(i,s) ) / N
```
- `U(i,s)` = number of use reports informant `i` gave for species `s`.
- Range `≥ 0` (unbounded); higher = more/more-varied uses.
- **Variant to confirm:** the Phillips & Gentry (1993) family counts *uses*; some
  authors average per informant differently. This spec uses the widely-cited
  "mean use-reports per informant" form. **Confirm the intended variant.**
- **Edge cases:** `N = 0` → N/A. An unlinked answer (no `catalog_species_id`)
  contributes no UR and must be excluded (and surfaced as "unlinked", not counted
  as a species).
- Source: Phillips & Gentry (1993).

### Number of Uses — NU

*How many use-categories a species is put to.*

```
NU(s) = |{ u : species s is cited in use-category u }|
```
- An integer count, `≥ 0`.
- **Edge cases:** a species cited without any use-category answer has `NU = 0`.
- Source: Prance et al. (1987).

### Cultural Importance Index — CI

*UV that respects use-categories.*

```
CI(s) = Σ_u ( UR(u,s) / N )
```
- `UR(u,s)` = use reports for species `s` in use-category `u`, summed over all
  categories.
- Theoretical max = number of use-categories. `CI ≥ UV` never; in the common
  single-UR-per-(informant,species,category) coding, `CI` equals the summed RFC
  across categories.
- **Requires use-categories** (blocked by the open decision above).
- Source: Tardío & Pardo-de-Santayana (2008).

### Relative Importance — RI

*A species' importance relative to the most-cited and most-versatile.*

```
RI(s) = ½ · ( RFC(s)/RFC_max + NU(s)/NU_max )
```
- `RFC_max`, `NU_max` = the maxima across all species in scope.
- Range `0–1`; the most-cited species reaches the RFC term = 1, the most-versatile
  reaches the NU term = 1.
- **Edge cases:** if `RFC_max` or `NU_max` = 0, that term contributes 0 (no
  division by zero).
- Source: Tardío & Pardo-de-Santayana (2008), after Pardo-de-Santayana (2003).

### Cultural Value — CV

*Breadth of use × how widely the species is known × how many uses reported.*

```
CV(s) = ( NU(s)/NC ) · RFC(s) · CI(s)
```
- `NC` = total number of use-categories considered in the study.
- **Edge cases:** `NC = 0` → `CV = 0`.
- Source: Reyes-García et al. (2006).

### Informant Consensus Factor — ICF (a.k.a. FIC)

*Agreement among informants within a use-category.*

```
ICF(u) = ( N_ur(u) − N_t(u) ) / ( N_ur(u) − 1 )
```
- `N_ur(u)` = number of use reports in category `u`.
- `N_t(u)` = number of distinct taxa (species) used in category `u`.
- Range `0–1`. Near 1 = strong consensus (many citations, few taxa) = a
  well-defined body of knowledge; near 0 = little agreement.
- Computed **per use-category**, not per species.
- **Edge cases:** if `N_ur(u) ≤ 1`, the denominator is 0 → ICF **undefined**;
  report N/A (never coerce to 0 or 1). If `N_t(u) = N_ur(u)` (every citation a
  different taxon), ICF = 0.
- **Requires use-categories.**
- Source: Trotter & Logan (1986).

### Fidelity Level — FL

*How concentrated a species' use is on one primary purpose.*

```
FL(s, u) = ( I_p(s, u) / I_u(s) ) × 100
```
- `I_p(s,u)` = informants who cited species `s` for the particular use `u`.
- `I_u(s)` = informants who cited species `s` for *any* use.
- Range `0–100 %`. 100 % = every informant who uses the species uses it for `u`.
- **Edge cases:** `I_u(s) = 0` → N/A. Reported for the (species, primary-use)
  pairs of interest.
- **Requires use-categories.**
- Source: Friedman et al. (1986).

---

## Worked example (canonical test fixture)

A minimal study: **N = 4 informants** (interviews I1–I4), **2 species**
(*Inga edulis* = A, *Cecropia peltata* = B), **2 use-categories**
(`food` = F, `medicine` = M). Each cell is a use report.

| Informant | A/food | A/medicine | B/food | B/medicine |
|---|:-:|:-:|:-:|:-:|
| I1 | ✓ | ✓ | ✓ |   |
| I2 | ✓ |   |   | ✓ |
| I3 | ✓ | ✓ |   | ✓ |
| I4 |   |   | ✓ |   |

**Derivation** (every count spelled out, so the fixture is checkable, not asserted
from memory):

- Use-report counts: `A/food = 3` (I1,I2,I3), `A/medicine = 2` (I1,I3),
  `B/food = 2` (I1,I4), `B/medicine = 2` (I2,I3).
- Frequency of citation (distinct informants per species): `FC(A) = 3`
  (I1,I2,I3 — **I4 never cites A**); `FC(B) = 4` (I1,I2,I3,I4 — **all four cite
  B**, via food or medicine).
- Use-reports per category: `N_ur(food) = 3+2 = 5`, `N_ur(medicine) = 2+2 = 4`;
  distinct taxa per category = {A,B} = 2 in both.
- Informants citing A for any use `I_u(A) = 3`; for B, `I_u(B) = 4`.
- Use breadth: `NU(A) = NU(B) = 2` (each used in food and medicine); total
  categories `NC = 2`. Maxima: `RFC_max = 1` (B), `NU_max = 2`.

**Expected values** (the exact assertions a test must reproduce):

| Index | Species/Cat | Value | Arithmetic |
|---|---|---|---|
| RFC | A | **0.75** | FC/N = 3/4 |
| RFC | B | **1.00** | 4/4 |
| NU | A | **2** | food, medicine |
| NU | B | **2** | food, medicine |
| UV | A | **1.25** | ΣUR/N = (3+2)/4 |
| UV | B | **1.00** | (2+2)/4 |
| CI | A | **1.25** | (3/4)+(2/4) |
| CI | B | **1.00** | (2/4)+(2/4) |
| RI | A | **0.875** | ½(0.75/1 + 2/2) |
| RI | B | **1.00** | ½(1/1 + 2/2) |
| CV | A | **0.9375** | (2/2)·0.75·1.25 |
| CV | B | **1.00** | (2/2)·1·1 |
| ICF | food | **0.75** | (N_ur−N_t)/(N_ur−1) = (5−2)/(5−1) |
| ICF | medicine | **0.6667** | (4−2)/(4−1) |
| FL | A, food | **100 %** | I_p/I_u = 3/3 |
| FL | B, medicine | **50 %** | I_p/I_u = 2/4 |

> These values were derived by hand from the table above; when implementing,
> encode this exact study as a fixture and assert the code reproduces each cell.
> (During the first draft of this spec, `FC(B)` was miswritten as 3 and the ICF
> rows were wrong — a live reminder of *why* the fixture must be derived cell by
> cell, not recalled.)

## Implementation notes

- **Answers are encrypted** (`InstanceAnswer.answer` cast `encrypted`), so indices
  cannot be computed in SQL — they are decrypted and aggregated in PHP, following
  the in-memory pattern of `InterviewDataTable` / `InterviewDataExport`.
  Implemented in `app/Services/EthnobiologyIndices.php`; species citations
  (FC/RFC/I_u) come from links directly; the use-report indices (NU, UV, CI, RI,
  CV, ICF, FL) additionally require a use-category.
- **Scope** is a project, restricted to its forms that carry a `link_to_species`
  field. `N` is the count of interviews of those forms — unrelated instruments in
  the same project are excluded so they can't dilute the denominator.
- **Unlinked answers** (null `catalog_species_id`) are excluded from species-level
  indices and should be surfaced as a data-quality figure ("X citations not yet
  linked") so a researcher knows the denominator of *linked* knowledge.
- **Gate** these behind `generate_reports` (all roles have it today) — see the
  access-control note in [../data-model.md](../data-model.md).
- **Charts.** The report page also offers three downloadable (PNG) charts, each
  from the same result: a species-by-index bar chart (selectable index), a
  species × use-category heatmap, and a species→use Sankey. These cover
  ethnobotanyR's `Radial_plot` / `ethnoChord` / `ethno_alluvial` (the chord is
  rendered as the more legible heatmap). Service exposes a per-species `uses`
  list ([{use_category, reports}]) to drive the heatmap and Sankey.

## Decisions & open points

- ✅ **Use-category modeling** — built as `InterviewItem.is_use_category`
  ([ADR 0007](../decisions/0007-use-category-as-item-role.md)).
- ✅ **Scope** — the ethnobotanyR species-level set (FC, NU, RFC, UV, CI, RI, CV)
  plus FL and ICF; the use-category role is a prerequisite of the milestone.
- ✅ **UV variant** — implemented as "mean use-reports per informant"
  (`UV(s) = ΣUR(s) / N`), matching the worked-example fixture. (In the fixture
  each informant gives at most one report per species-use, so this coincides with
  the Phillips & Gentry uses-based reading.)

## Sources

- Prance, G. T., Balée, W., Boom, B. M., & Carneiro, R. L. (1987). *Quantitative
  ethnobotany and the case for conservation in Amazonia.* Conservation Biology
  1(4), 296–310. — NU
- Phillips, O., & Gentry, A. H. (1993). *The useful plants of Tambopata, Peru.*
  Economic Botany 47(1), 15–32. — UV
- Trotter, R. T., & Logan, M. H. (1986). *Informant consensus.* In *Plants in
  Indigenous Medicine and Diet.* Redgrave. — ICF
- Friedman, J., Yaniv, Z., Dafni, A., & Palewitch, D. (1986). *A preliminary
  classification of the healing potential of medicinal plants…* J. Ethnopharmacology 16. — FL
- Tardío, J., & Pardo-de-Santayana, M. (2008). *Cultural importance indices: a
  comparative analysis…* Economic Botany 62(1), 24–39. — RFC, CI, RI
- Reyes-García, V., Huanca, T., Vadez, V., & Leonard, W. (2006). *Cultural,
  practical and economic value of wild plants…* Economic Botany 60(1), 62–74. — CV

**Tooling inspiration** (implements these same literature indices — not their
origin; the primary sources above define them):

- Whitney, C. *ethnobotanyR: Calculate Quantitative Ethnobotany Indices.* R
  package, CRAN. <https://CRAN.R-project.org/package=ethnobotanyR>

The report page surfaces these per-index citations plus the ethnobotanyR
attribution, so researchers can cite the right source for each index. The xlsx
export carries them too, on a second **References** sheet (CSV, a single flat
table, keeps just the indices).
