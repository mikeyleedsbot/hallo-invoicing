/**
 * Koppelen zonder de pagina te herladen.
 *
 * Het formulier gaat via fetch naar dezelfde route, daarna worden alleen de
 * blokken van het matchscherm opnieuw opgehaald en omgewisseld. De server
 * blijft dus bepalen wat er staat; alleen het herladen van de hele pagina en
 * het terugspringen naar boven vervalt.
 */
export default function registreerBankMatch(Alpine) {
    Alpine.data('bankMatch', () => ({
        bezig: false,
        melding: '',
        foutmelding: '',

        /** Verstuur een koppel- of ontkoppelformulier zonder navigatie. */
        async verstuur(form) {
            if (this.bezig) {
                return;
            }

            const knop = form.querySelector('[type="submit"]');
            const kaart = form.closest('[data-transactie]');
            const anker = this.ankerVan(kaart);

            this.bezig = true;
            this.melding = '';
            this.foutmelding = '';
            if (knop) {
                knop.disabled = true;
            }

            try {
                const antwoord = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });

                const data = await antwoord.json().catch(() => ({}));

                if (! antwoord.ok) {
                    // 419 betekent verlopen sessie: dan is gewoon herladen juister
                    if (antwoord.status === 419) {
                        form.submit();
                        return;
                    }

                    this.toon('', data.message || 'Koppelen is niet gelukt.');
                    return;
                }

                await this.ververs(anker);
                this.toon(data.message || 'Gelukt.', '');
            } catch (fout) {
                // Netwerk weg of iets anders onverwachts: terugvallen op de
                // gewone werkwijze, zodat de actie hoe dan ook doorgaat
                form.submit();
            } finally {
                this.bezig = false;
                if (knop) {
                    knop.disabled = false;
                }
            }
        },

        /** Onthoud welke kaart waar stond, zodat we daar straks weer staan. */
        ankerVan(kaart) {
            if (! kaart) {
                return null;
            }

            const volgende = kaart.nextElementSibling;

            return {
                id: kaart.dataset.transactie,
                volgendeId: volgende?.dataset?.transactie ?? null,
                top: kaart.getBoundingClientRect().top,
            };
        },

        /** Haal de blokken opnieuw op en wissel ze om. */
        async ververs(anker) {
            const html = await fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            }).then((r) => r.text());

            const vers = new DOMParser().parseFromString(html, 'text/html');

            ['blok-gekoppeld', 'blok-eerder', 'blok-te-matchen'].forEach((id) => {
                this.wisselOm(id, vers);
            });

            // Alpine zet open- en dichtstaande blokken pas in de volgende tik
            // terug; meten we daarvoor, dan verschuift de pagina daarna alsnog
            await Alpine.nextTick();
            requestAnimationFrame(() => this.herstelPositie(anker));
        },

        /** Eén blok vervangen, met behoud van of het open- of dichtstond. */
        wisselOm(id, vers) {
            const oud = document.getElementById(id);
            const nieuw = vers.getElementById(id);

            if (! oud) {
                return;
            }

            const stond = this.staatVan(oud);

            if (! nieuw) {
                oud.innerHTML = '';
                return;
            }

            Alpine.destroyTree?.(oud);
            oud.innerHTML = nieuw.innerHTML;
            Alpine.initTree(oud);

            const nu = this.staatVan(oud);
            if (nu && stond) {
                nu.open = stond.open;
                if ('alles' in stond) {
                    nu.alles = stond.alles;
                }
            }
        },

        staatVan(wrapper) {
            const kern = wrapper.firstElementChild;

            if (! kern || ! kern.hasAttribute('x-data')) {
                return null;
            }

            try {
                return Alpine.$data(kern);
            } catch (fout) {
                return null;
            }
        },

        /**
         * Zet het scherm terug op de plek waar gewerkt werd. Is de kaart
         * helemaal gekoppeld en dus verdwenen, dan houden we de kaart eronder
         * aan; die staat nu op dezelfde plek in de lijst.
         */
        herstelPositie(anker) {
            if (! anker) {
                return;
            }

            const doel = document.querySelector(`[data-transactie="${anker.id}"]`)
                ?? (anker.volgendeId ? document.querySelector(`[data-transactie="${anker.volgendeId}"]`) : null);

            if (! doel) {
                return;
            }

            window.scrollBy({ top: doel.getBoundingClientRect().top - anker.top, behavior: 'instant' });
        },

        toon(melding, foutmelding) {
            this.melding = melding;
            this.foutmelding = foutmelding;

            if (melding) {
                window.clearTimeout(this.timer);
                this.timer = window.setTimeout(() => {
                    this.melding = '';
                }, 4000);
            }
        },
    }));
}
