/** Feed / rundown IDs mirror legacy trans_wip/index.blade.php constants */
export const WIP_IDS = {
  rundown_cpko: '000',
  feed_cpko: '001',
  feed_daoil: '002',
  feed_crudeme: '003',
  feed_treatedgly: '004',
  feed_ume: '005',
  feed_me28: '006-01',
  feed_me80_105: '006-02',
  feed_crudegly: '007',
  feed_cfa28: '008',
  feed_ecorolwax: '009',
  feed_112_fa24: '009-01',
  feed_112_fa14lrr: '009-02',
  feed_112_fa18lrr: '009-03',
  feed_112_ecowax: '009-04',
  rundown_daoil: '011',
  rundown_crudeme: '012',
  rundown_me60: '013',
  rundown_crudegly: '014',
  rundown_wme: '015',
  rundown_cfa28: '016',
  rundown_glycerine: '017',
  rundown_ecorolwax: '018',
  rundown_112_ecowax: '019',
  rundown_pkfad: '021',
  rundown_treatedgly: '022',
  rundown_bdme: '023',
  rundown_me28_302: '025',
  rundown_105_cfa80: '026',
  rundown_lefa: '028',
  rundown_112_fa18: '029',
  rundown_ume: '033',
  rundown_fa24: '038',
  rundown_112_fa12: '039',
  rundown_me28: '043',
  rundown_fa16: '048',
  rundown_112_fa18lrr: '049',
  rundown_fa18lrr: '058',
  rundown_112_fa14: '059',
  rundown_104_me80: '063',
  rundown_112_cfa28: '069',
  rundown_112_fa14lrr: '079',
  rundown_106_fa1299: '078',
  rundown_106_fa1499: '088',
  rundown_econoate665: '053',
}

function feed(id, title, entryLabel, feedId, opts = {}) {
  return {
    id,
    kind: 'feed',
    panelGroup: 'feed',
    title,
    entryLabel,
    feedId,
    balanceRundownId: opts.balanceRundownId ?? null,
    showBalance: opts.showBalance ?? false,
    hideTank: opts.hideTank !== false,
    quantifierDcs: opts.quantifierDcs ?? null,
    sectionMode: opts.sectionMode ?? null,
    logPrefix: opts.logPrefix ?? title,
  }
}

function rundown(id, title, entryLabel, rundownId, opts = {}) {
  return {
    id,
    kind: 'rundown',
    panelGroup: 'rundown',
    title,
    entryLabel,
    rundownId,
    balanceRundownId: rundownId,
    showBalance: true,
    quantifierDcs: opts.quantifierDcs ?? null,
    sectionMode: opts.sectionMode ?? null,
    logPrefix: opts.logPrefix ?? title,
  }
}

export const wipSectionFilterOptions = [
  { value: 'allSection', label: '- All Section -' },
  { value: 'section101', label: '- Section 101/102 -' },
  { value: 'section103', label: '- Section 103 -' },
  { value: 'section104', label: '- Section 104 -' },
  { value: 'section105', label: '- Section 105 -' },
  { value: 'section106', label: '- Section 106/114 -' },
  { value: 'section110', label: '- Section 110 -' },
  { value: 'section111', label: '- Section 111/116 -' },
  { value: 'section112', label: '- Section 112/114 -' },
  { value: 'section302', label: '- Section 302 -' },
]

export const wipMode105Options = [
  { value: '105-long', label: '- Mode LONG-CHAIN -' },
  { value: '105-short', label: '- Mode SHORT-CHAIN -' },
]

export const wipMode106Options = [
  { value: '106-ecorol24', label: '- Mode ECOROL 24 -' },
  { value: '106-ecorol1214', label: '- Mode ECOROL 12/14 -' },
]

export const wipMode112Options = [
  { value: '112-fa18lrr', label: '- Mode FA18lrr -' },
  { value: '112-fa24', label: '- Mode FA24 -' },
  { value: '112-fa14lrr', label: '- Mode FA14lrr -' },
  { value: '112-ecorolwax', label: '- Mode Ecorol Wax -' },
]

export const wipSections = [
  {
    id: 'section101',
    title: 'SECTION 101/102',
    panels: [
      feed('cpko-feed', 'RM FEEDS', 'RM Feed (101 FT0113)', WIP_IDS.feed_cpko, {
        showBalance: true,
        balanceRundownId: WIP_IDS.rundown_cpko,
        quantifierDcs: '101_FT0113',
      }),
      rundown('daoil-rundown', 'DA-OIL RUNDOWNS', 'DA-OIL Rundown (102 FT0109)', WIP_IDS.rundown_daoil, {
        quantifierDcs: '102_FT0109',
      }),
      rundown('pkfad-rundown', 'PKFAD RUNDOWNS', 'PKFAD Rundown (102 FT0129)', WIP_IDS.rundown_pkfad, {
        quantifierDcs: '102_FT0129',
      }),
    ],
  },
  {
    id: 'section103',
    title: 'SECTION 103',
    panels: [
      feed('daoil-feed', 'DA-OIL FEEDS', 'DA-OIL Feed (103 FT0101)', WIP_IDS.feed_daoil, {
        quantifierDcs: '103_FT0101',
      }),
      rundown('crudeme-rundown-103', 'CRUDE-ME RUNDOWNS', 'CRUDE-ME Rundown (103 FT0329)', WIP_IDS.rundown_crudeme, {
        quantifierDcs: '103_FT0329',
      }),
      rundown('treatedgly-rundown-103', 'TREATED-GLYCERINE RUNDOWNS', 'TREATED-GLY Rundown (103 FT0266)', WIP_IDS.rundown_treatedgly, {
        quantifierDcs: '103_FT0266',
      }),
    ],
  },
  {
    id: 'section104',
    title: 'SECTION 104',
    panels: [
      feed('crudeme-feed', 'CRUDE-ME FEEDS', 'CRUDE-ME Feed (104 F0118)', WIP_IDS.feed_crudeme, {
        quantifierDcs: '104_FT0118',
      }),
      rundown('ume-rundown-104', 'UME RUNDOWNS', 'UME Rundown (104 F0110)', WIP_IDS.rundown_ume, {
        quantifierDcs: '104_F0110',
      }),
      rundown('bdme-rundown', 'BDME RUNDOWNS', 'BDME Rundown (104 F0215)', WIP_IDS.rundown_bdme, {
        quantifierDcs: '104_F0215',
      }),
      rundown('me28-rundown-104', 'ME28 RUNDOWNS', 'ME28 Rundown (104 F0332)', WIP_IDS.rundown_me28, {
        quantifierDcs: '104_F0332',
      }),
      rundown('econoate665-rundown', 'ECONOATE 6/65 RUNDOWNS', 'ECONOATE 6/65 Rundown (104 FO170)', WIP_IDS.rundown_econoate665, {
        quantifierDcs: '104FT0170',
      }),
      rundown('me80-104-rundown', 'ME80 RUNDOWNS', 'ME80 Rundown (104 FO157)', WIP_IDS.rundown_104_me80, {
        quantifierDcs: '104_F0157',
      }),
    ],
  },
  {
    id: 'section105',
    title: 'SECTION 105',
    modeOptions: wipMode105Options,
    defaultMode: '105-long',
    panels: [
      feed('me28-feed', 'ME28 FEEDS', 'ME28 Feed (105 FQ104)', WIP_IDS.feed_me28, {
        sectionMode: '105-long',
        quantifierDcs: '105_FQ104',
      }),
      rundown('cfa28-rundown-105', 'CFA28 RUNDOWNS', 'CFA28 Rundown (105 FQ808)', WIP_IDS.rundown_cfa28, {
        sectionMode: '105-long',
        quantifierDcs: '105_FQ808',
      }),
      feed('me80-feed', 'ME80 FEEDS', 'ME80 Feed (105 FQ104)', WIP_IDS.feed_me80_105, {
        sectionMode: '105-short',
        quantifierDcs: '105_FQ104',
      }),
      rundown('cfa80-105-rundown', 'CFA80 RUNDOWNS', 'CFA80 Rundown (105 FQ808)', WIP_IDS.rundown_105_cfa80, {
        sectionMode: '105-short',
        quantifierDcs: '105_FQ808',
      }),
    ],
  },
  {
    id: 'section106',
    title: 'SECTION 106/114',
    modeOptions: wipMode106Options,
    defaultMode: '106-ecorol24',
    panels: [
      feed('cfa28-feed', 'CFA28 FEEDS', 'CFA28 Feed (106 F0115)', WIP_IDS.feed_cfa28, {
        showBalance: true,
        balanceRundownId: WIP_IDS.rundown_cfa28,
        quantifierDcs: '106_F0115',
      }),
      rundown('ecorolwax-rundown', 'ECOROL-WAX RUNDOWNS', 'ECOROL-WAX Rundown (106 F0245)', WIP_IDS.rundown_ecorolwax, {
        quantifierDcs: '106_F0245',
      }),
      rundown('lefa-rundown', 'LEFA RUNDOWNS', 'LEFA Rundown (106 F0167)', WIP_IDS.rundown_lefa, {
        quantifierDcs: '106_F0167',
      }),
      rundown('fa18lrr-rundown-106', 'FA18lrr RUNDOWNS', 'FA18lrr Rundown (106 F0112)', WIP_IDS.rundown_fa18lrr, {
        quantifierDcs: '106_F0112',
      }),
      rundown('fa24-rundown', 'FA24 RUNDOWNS', 'FA24 Rundown (106 F0134)', WIP_IDS.rundown_fa24, {
        sectionMode: '106-ecorol24',
        quantifierDcs: '106_F0134',
      }),
      rundown('fa16-rundown', 'FA16/99 RUNDOWNS', 'FA16/99 Rundown (106 F0231)', WIP_IDS.rundown_fa16, {
        sectionMode: '106-ecorol24',
        quantifierDcs: '106_F0231',
      }),
      rundown('106-fa1299-rundown', 'FA12/99 RUNDOWNS', 'FA12/99 Rundown (106 F0134)', WIP_IDS.rundown_106_fa1299, {
        sectionMode: '106-ecorol1214',
        quantifierDcs: '106_F0134',
      }),
      rundown('106-fa1499-rundown', 'FA14/99 RUNDOWNS', 'FA14/99 Rundown (106 F0231)', WIP_IDS.rundown_106_fa1499, {
        sectionMode: '106-ecorol1214',
        quantifierDcs: '106_F0231',
      }),
    ],
  },
  {
    id: 'section110',
    title: 'SECTION 110',
    panels: [
      feed('treatedgly-feed', 'TREATED-GLYCERINE FEEDS', 'TREATED-GLY Feed (110 F0107)', WIP_IDS.feed_treatedgly, {
        quantifierDcs: '110_F0107',
      }),
      rundown('crudegly-rundown-110', 'CRUDE-GLY RUNDOWNS', 'CRUDE-GLY Rundown (110 F0108)', WIP_IDS.rundown_crudegly, {
        quantifierDcs: '110_F0108',
      }),
    ],
  },
  {
    id: 'section111',
    title: 'SECTION 111/116',
    panels: [
      feed('crudegly-feed', 'CRUDE-GLY FEEDS', 'CRUDE-GLY Feed (111 F0118 + 116 FC01)', WIP_IDS.feed_crudegly, {
        quantifierDcs: '111_F0118_116_FC01',
      }),
      rundown('glycerine-rundown', 'GLYCERINE RUNDOWNS', 'GLYCERINE Rundown (111 FT0314 + 116 FT02)', WIP_IDS.rundown_glycerine, {
        quantifierDcs: '111_FT0314_116_FT02',
      }),
    ],
  },
  {
    id: 'section112',
    title: 'SECTION 112/114',
    modeOptions: wipMode112Options,
    defaultMode: '112-fa18lrr',
    panels: [
      feed('112-fa24-feed', 'FA24 FEEDS', 'FA24 Feed (112 F0109)', WIP_IDS.feed_112_fa24, {
        sectionMode: '112-fa24',
        showBalance: true,
        balanceRundownId: WIP_IDS.rundown_fa24,
        quantifierDcs: '112_F0109',
        logPrefix: 'SECT 112 - FA24',
      }),
      rundown('112-cfa28-rundown-fa24', 'CFA28 RUNDOWNS', 'CFA28 Rundown (112 F0139)', WIP_IDS.rundown_112_cfa28, {
        sectionMode: '112-fa24',
        quantifierDcs: '112_F0139',
      }),
      rundown('112-fa1299-rundown', 'FA12/99 RUNDOWNS', 'FA12/99 Rundown (112 F0235)', WIP_IDS.rundown_112_fa12, {
        sectionMode: '112-fa24',
        quantifierDcs: '112_F0235',
      }),
      rundown('112-fa14lrr-rundown-fa24', 'FA14lrr RUNDOWNS', 'FA14lrr Rundown (112 F0224)', WIP_IDS.rundown_112_fa14lrr, {
        sectionMode: '112-fa24',
        quantifierDcs: '112_F0224',
      }),
      feed('112-fa14lrr-m2-feed', 'FA14lrr FEEDS', 'FA14lrr Feed (112 F0109)', WIP_IDS.feed_112_fa14lrr, {
        sectionMode: '112-fa14lrr',
        showBalance: true,
        balanceRundownId: WIP_IDS.rundown_112_fa14lrr,
        quantifierDcs: '112_F0109',
        logPrefix: 'SECT 112 - FA14lrr',
      }),
      rundown('112-cfa28-m2-rundown', 'CFA28 RUNDOWNS', 'CFA28 Rundown (112 F0139 + 112 F0224)', WIP_IDS.rundown_112_cfa28, {
        sectionMode: '112-fa14lrr',
        quantifierDcs: '112_F0139_112_F0224',
      }),
      rundown('112-fa1499-m2-rundown', 'FA14/99 RUNDOWNS', 'FA14/99 Rundown (112 F0235)', WIP_IDS.rundown_112_fa14, {
        sectionMode: '112-fa14lrr',
        quantifierDcs: '112_F0224',
      }),
      feed('112-fa18lrr-m4-feed', 'FA18lrr FEEDS', 'FA18lrr Feed (112 F0109)', WIP_IDS.feed_112_fa18lrr, {
        sectionMode: '112-fa18lrr',
        showBalance: true,
        balanceRundownId: WIP_IDS.rundown_112_fa18lrr,
        quantifierDcs: '112_F0109',
        logPrefix: 'SECT 112 - FA18lrr',
      }),
      rundown('112-cfa28-m4-rundown', 'CFA28 RUNDOWNS', 'CFA28 Rundown (112 F0139)', WIP_IDS.rundown_112_cfa28, {
        sectionMode: '112-fa18lrr',
        quantifierDcs: '112_F0139',
      }),
      rundown('112-fa1899-m4-rundown', 'FA18/99 RUNDOWNS', 'FA18/99 Rundown (112 F0235)', WIP_IDS.rundown_112_fa18, {
        sectionMode: '112-fa18lrr',
        quantifierDcs: '112_F0235',
      }),
      rundown('112-ecowax-m4-rundown', 'ECOROL WAX RUNDOWNS', 'ECOROL WAX Rundown (112 F0224)', WIP_IDS.rundown_112_ecowax, {
        sectionMode: '112-fa18lrr',
        quantifierDcs: '112_F0224',
      }),
      feed('112-ecowax-m3-feed', 'ECOROL WAX FEEDS', 'ECOROL WAX Feed (112 F0109)', WIP_IDS.feed_112_ecowax, {
        sectionMode: '112-ecorolwax',
        showBalance: true,
        balanceRundownId: WIP_IDS.rundown_ecorolwax,
        quantifierDcs: '112_F0109',
        logPrefix: 'SECT 112 - ECOROL WAX',
      }),
      rundown('112-cfa28-m3-rundown', 'CFA28 RUNDOWNS', 'CFA28 Rundown (112 F0139)', WIP_IDS.rundown_112_cfa28, {
        sectionMode: '112-ecorolwax',
        quantifierDcs: '112_F0139',
      }),
      rundown('112-fa18lrr-m3-rundown', 'FA18lrr RUNDOWNS', 'FA18lrr Rundown (112 F0235)', WIP_IDS.rundown_112_fa18lrr, {
        sectionMode: '112-ecorolwax',
        quantifierDcs: '112_F0235',
      }),
      rundown('112-ecowax-m3-rundown', 'ECOROL WAX RUNDOWNS', 'ECOROL WAX Rundown (112 F0224)', WIP_IDS.rundown_112_ecowax, {
        sectionMode: '112-ecorolwax',
        quantifierDcs: '112_F0224',
      }),
    ],
  },
  {
    id: 'section302',
    title: 'SECTION 302',
    panels: [
      feed('ume-feed-302', 'UME FEEDS', 'UME Feed (302 FT102)', WIP_IDS.feed_ume, {
        quantifierDcs: '302_FT102',
      }),
      rundown('wme-rundown', 'WME RUNDOWNS', 'WME Rundown (302 FT101)', WIP_IDS.rundown_wme, {
        quantifierDcs: '302_FT101',
      }),
      rundown('me28-302-rundown', 'ME28-302 RUNDOWNS', 'ME28-302 Rundown (302V04)', WIP_IDS.rundown_me28_302, {
        quantifierDcs: '302V04',
      }),
    ],
  },
]

export function panelVisible(panel, sectionMode) {
  if (!panel.sectionMode) return true
  return panel.sectionMode === sectionMode
}

export function sectionPanels(section, sectionMode) {
  const mode = section.modeOptions ? sectionMode ?? section.defaultMode : null
  return section.panels.filter((panel) => panelVisible(panel, mode))
}

export function sectionFeedPanels(section, sectionMode) {
  return sectionPanels(section, sectionMode).filter((p) => p.panelGroup === 'feed')
}

export function sectionRundownPanels(section, sectionMode) {
  return sectionPanels(section, sectionMode).filter((p) => p.panelGroup === 'rundown')
}

export function sectionHasProcessDivider(section, sectionMode) {
  const panels = sectionPanels(section, sectionMode)
  return panels.some((p) => p.panelGroup === 'feed') && panels.some((p) => p.panelGroup === 'rundown')
}
