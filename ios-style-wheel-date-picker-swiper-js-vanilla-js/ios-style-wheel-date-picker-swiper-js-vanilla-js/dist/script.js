const ROW_H   = 48;
  const VISIBLE = 5;
  const OFFSET  = ((VISIBLE - 1) / 2) * ROW_H; // 96px

  const MONTHS = ["January","February","March","April","May","June",
                  "July","August","September","October","November","December"];
  const YEARS  = Array.from({ length: 171 }, (_, i) => 1930 + i);

  const today = new Date();
  let pickedMonth = today.getMonth();
  let pickedDay   = today.getDate() - 1;
  let pickedYear  = today.getFullYear();

  function daysInMonth(m, y) { return new Date(y, m + 1, 0).getDate(); }
  function buildDayList(m, y) {
    return Array.from({ length: daysInMonth(m, y) }, (_, i) => String(i + 1).padStart(2, "0"));
  }
  function fillColumn(id, items) {
    const w = document.querySelector("#" + id + " .swiper-wrapper");
    w.innerHTML = "";
    items.forEach(item => {
      const s = document.createElement("div");
      s.className = "swiper-slide";
      s.textContent = item;
      w.appendChild(s);
    });
  }

  fillColumn("swMonth", MONTHS.map(m => m.slice(0, 3).toUpperCase()));
  fillColumn("swDay",   buildDayList(pickedMonth, pickedYear));
  fillColumn("swYear",  YEARS);

  // Live preview updater
  function updatePreview() {
    const m  = MONTHS[pickedMonth].slice(0, 3).toUpperCase();
    const d  = String(pickedDay + 1).padStart(2, "0");
    const y  = pickedYear;
    const el = document.getElementById("livePreview");
    el.childNodes[0].textContent = m + " " + d;
    el.querySelector(".preview-year").textContent = y;
  }

  function createWheel(id, startIndex, onChanged) {
    return new Swiper("#" + id, {
      direction: "vertical",
      slidesPerView: VISIBLE,
      centeredSlides: false,
      slidesOffsetBefore: OFFSET,
      slidesOffsetAfter: OFFSET,
      initialSlide: startIndex,
      slideToClickedSlide: true,
      resistanceRatio: 0,
      freeMode: {
        enabled: true,
        sticky: true,
        momentumRatio: 0.2,
        momentumVelocityRatio: 0.25,
      },
      mousewheel: { enabled: true },
      on: {
        slideChange(sw) { onChanged(sw.activeIndex); updatePreview(); },
      },
    });
  }

  let monthWheel, dayWheel, yearWheel;

  monthWheel = createWheel("swMonth", pickedMonth, idx => {
    pickedMonth = idx;
    rebuildDayWheel();
  });
  dayWheel = createWheel("swDay", pickedDay, idx => {
    pickedDay = idx;
  });
  yearWheel = createWheel("swYear", YEARS.indexOf(pickedYear), idx => {
    pickedYear = YEARS[idx];
    rebuildDayWheel();
  });

  function rebuildDayWheel() {
    if (!dayWheel) return;
    const days  = buildDayList(pickedMonth, pickedYear);
    const maxIdx = days.length - 1;
    if (pickedDay > maxIdx) pickedDay = maxIdx;
    fillColumn("swDay", days);
    dayWheel.update();
    dayWheel.slideTo(pickedDay, 0, false);
  }

  // Init live preview
  updatePreview();

  document.getElementById("confirmBtn").addEventListener("click", () => {
    const m = MONTHS[monthWheel.activeIndex];
    const d = String(dayWheel.activeIndex + 1).padStart(2, "0");
    const y = YEARS[yearWheel.activeIndex];
    document.getElementById("dateOut").value = `${m} ${d}, ${y}`;
  });