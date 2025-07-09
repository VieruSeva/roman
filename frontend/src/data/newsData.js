// Static news data for the ANIPM website
// This file replaces the previous API calls to the backend

export const newsTickerData = {
  success: true,
  items: [
    {
      id: 1,
      title: "R. Moldova exportă mai multă făină, dar la un preț mult mai mic",
      type: "news",
      url: "https://agroexpert.md/rom/novosti/r-moldova-exporta-mai-multa-faina-dar-la-un-pret-mult-mai-mic"
    },
    {
      id: 2,
      title: "Tot mai mulți pasionați de panificație descoperă farmecul pâinii cu maia",
      type: "news",
      url: "https://stiri.md/article/social/tot-mai-multi-pasionati-de-panificatie-descopera-farmecul-painii-cu-maia/"
    },
    {
      id: 3,
      title: "AGENDA TRANZIȚIEI ENERGETICE - ediția a 4-a",
      type: "event",
      url: "/noutati"
    },
    {
      id: 4,
      title: "Expoziția IPAC IMA – 27-30 mai 2025, Milano, Italia",
      type: "event",
      url: "/noutati"
    },
    {
      id: 5,
      title: "Conferința Internațională de Panificație - București 2025",
      date: "15 septembrie 2025",
      type: "event",
      url: "/noutati"
    },
    {
      id: 6,
      title: "Noi reglementări pentru etichetarea produselor de panificație",
      type: "news",
      url: "/noutati"
    },
    {
      id: 7,
      title: "Târgul de produse tradiționale - Chișinău 2025",
      date: "10 august 2025",
      type: "event",
      url: "/noutati"
    },
    {
      id: 8,
      title: "Inovații în industria de morărit prezentate la expoziția IBA",
      type: "news",
      url: "/noutati"
    },
    {
      id: 10,
      title: "Creșterea prețurilor la făină afectează industria de panificație",
      type: "news",
      url: "/noutati"
    },
    {
      id: 11,
      title: "Noi oportunități de finanțare pentru brutăriile artizanale",
      type: "news",
      url: "/noutati"
    },
    {
      id: 12,
      title: "Workshop: Tehnici moderne de panificație - Bălți",
      date: "5 iulie 2025",
      type: "event",
      url: "/noutati"
    },
    {
      id: 13,
      title: "Studiu: Consumul de pâine integrală în creștere în Republica Moldova",
      type: "news",
      url: "/noutati"
    },
    {
      id: 14,
      title: "Forum al Brutarilor din Republica Moldova - ediția a VII-a",
      date: "20 octombrie 2025",
      type: "event",
      url: "/noutati"
    }
  ],
  total: 13
};

// News data for the TransitionGalleryPage
export const transitionNewsData = {
  id: 9,
  title: "Tranziția Verde a Republicii Moldova: Motor al Integrării Europene și Dezvoltării Durabile",
  date: "24 iunie 2025",
  author: "Asociația Națională a Industriei de Panificație și Morărit",
  readTime: "5 minute de citire",
  summary: "Conferința Națională dedicată tranziției verzi în industria alimentară a reunit experți, producători și autorități pentru a discuta despre viitorul sustenabil al sectorului.",
  fullContent: "Asociația Națională a Industriei de Panificație și Morărit din Republica Moldova a organizat Conferința Națională cu tema \"Tranziția Verde: Oportunități și Provocări pentru Industria Alimentară\", un eveniment care a reunit peste 150 de participanți din întreaga țară.\n\nConferința a abordat subiecte esențiale precum:\n\n• Implementarea tehnologiilor eco-eficiente în procesele de producție\n• Reducerea amprentei de carbon în lanțul valoric alimentar\n• Oportunități de finanțare pentru proiecte verzi\n• Adaptarea la noile cerințe legislative europene în contextul integrării\n• Certificarea produselor ecologice și accesul la piețele internaționale\n\nMinistrul Agriculturii și Industriei Alimentare, prezent la eveniment, a subliniat importanța tranziției verzi: \"Sectorul alimentar trebuie să fie în prima linie a transformării economiei naționale spre sustenabilitate. Avem resurse, avem expertiză și, cel mai important, avem determinarea de a face această tranziție.\"\n\nReprezentanții asociației au prezentat un plan de acțiune pe 5 ani care include măsuri concrete pentru reducerea consumului de energie, implementarea tehnologiilor de producție curate și minimizarea deșeurilor alimentare.\n\nEvenimentul s-a încheiat cu semnarea unui memorandum de colaborare între asociațiile din industria alimentară, autoritățile publice și instituțiile academice pentru accelerarea tranziției verzi în sectorul agroalimentar din Republica Moldova.",
  images: [
    "/images/trans1.jpg",
    "/images/trans2.jpg",
    "/images/trans3.jpg",
    "/images/trans4.jpg",
    "/images/trans5.jpg",
    "/images/trans6.jpg",
    "/images/trans7.jpg"
  ],
  category: "evenimente",
  tags: ["tranziție verde", "sustenabilitate", "industrie alimentară", "conferință"]
};

// News data for the LatestNewsPage
export const allNewsData = [
  {
    id: 9,
    title: "Tranziția Verde a Republicii Moldova: Motor al Integrării Europene și Dezvoltării Durabile",
    date: "24 iunie 2025",
    summary: "Conferința Națională dedicată tranziției verzi în industria alimentară a reunit experți, producători și autorități pentru a discuta despre viitorul sustenabil al sectorului.",
    image: "/images/trans1.jpg",
    category: "evenimente",
    featured: true,
    url: "/galerie-tranzitie"
  },
  {
    id: 1,
    title: "R. Moldova exportă mai multă făină, dar la un preț mult mai mic",
    date: "20 iunie 2025",
    summary: "În primele cinci luni ale anului 2025, Republica Moldova a exportat cu 15% mai multă făină decât în aceeași perioadă a anului trecut, însă prețul mediu a scăzut cu aproximativ 8%.",
    image: "/images/flour.jpg",
    category: "industrie",
    featured: true,
    url: "https://agroexpert.md/rom/novosti/r-moldova-exporta-mai-multa-faina-dar-la-un-pret-mult-mai-mic"
  },
  {
    id: 2,
    title: "Tot mai mulți pasionați de panificație descoperă farmecul pâinii cu maia",
    date: "15 iunie 2025",
    summary: "Trendul global al pâinii cu maia a ajuns și în Republica Moldova, tot mai mulți pasionați de panificație descoperind beneficiile și gustul deosebit al acestui tip de pâine.",
    image: "/images/bakery.jpg",
    category: "tendințe",
    featured: false,
    url: "https://stiri.md/article/social/tot-mai-multi-pasionati-de-panificatie-descopera-farmecul-painii-cu-maia/"
  },
  {
    id: 3,
    title: "AGENDA TRANZIȚIEI ENERGETICE - ediția a 4-a",
    date: "10 iulie 2025",
    summary: "Cea de-a patra ediție a conferinței dedicate tranziției energetice va avea loc la Chișinău, cu focus pe soluții pentru industria alimentară.",
    image: "/images/energy2025.mp4",
    category: "evenimente",
    featured: false,
    url: "/noutati"
  }
]


