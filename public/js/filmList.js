const search_bar = document.getElementById("search-input");
const sort_by = document.getElementById("sort-by");
const sort_order = document.getElementById("sort-order");
const genre = document.getElementById("filter-genre");
const released_year = document.getElementById("filter-year");
const urlParams = new URLSearchParams(window.location.search);
const paginationData = document.getElementById("pagination-data");
let totalPageCount = paginationData.getAttribute("data-total-pages");
let currentPage = parseInt(urlParams.get("page")) || 1;
const film_cards = document.getElementById("film-card-list");

const utils = new Utils();
async function searchFilterHandler() {
  try {
    const httpClient = new HttpClient();
    const search = search_bar.value !== undefined ? search_bar.value : "";
    let url = `/search?q=${search_bar.value}&genre=${genre.value}&year=${released_year.value}&order=${sort_by.value}&sort=${sort_order.value}&page=${currentPage}`;

    httpClient.get(url).then((response) => {
      console.log(response);
      if (response.status === 200) {
        const responseData = response.data;
        updateFilmCards(responseData["films"]);
        totalPageCount = responseData["total_page"];
        generatePaginationLinks();
      } else {
        console.error("Error:", response);
      }
    });
  } catch (e) {
    console.error("Error: ", e);
  }
}

function generatePaginationLinks() {
  const paginationContainer = document.getElementById("pagination-container");
  paginationContainer.innerHTML = "";
  console.log(totalPageCount);

  for (let i = 1; i <= totalPageCount; i++) {
    const pageLink = document.createElement("a");
    pageLink.textContent = i;
    pageLink.href = `?page=${i}`;
    pageLink.classList.add("page-number");
    if (i === currentPage) {
      pageLink.classList.add("active");
    }
    pageLink.addEventListener("click", (e) => {
      e.preventDefault();
      currentPage = i;
      searchFilterHandler();
    });

    paginationContainer.appendChild(pageLink);
  }
}

function updateFilmCards(films) {
  film_cards.innerHTML = "";

  film_cards.innerHTML = films
    .map(
      (film) => `
        <div class='film-card'>
            <a href='/film-details?film_id=${film.film_id}'>
            <div class='film-image' style="background-image: url('public/${film.image_path}');"></div>
            <div class='film-title'>${film.title}</div>
        </div>
    `
    )
    .join("");
}

for (let i = 1; i <= totalPageCount; i++) {
  const pageLink = document.getElementById(`page-${i}`);
  if (pageLink) {
    pageLink.addEventListener("click", (e) => {
      e.preventDefault();
      currentPage = i;
      searchFilterHandler();
    });
  }
}

search_bar.addEventListener("input", utils.debounce(searchFilterHandler, 300));
sort_by.addEventListener("change", utils.debounce(searchFilterHandler, 300));
sort_order.addEventListener("change", utils.debounce(searchFilterHandler, 300));
genre.addEventListener("change", utils.debounce(searchFilterHandler, 300));
released_year.addEventListener(
  "change",
  utils.debounce(searchFilterHandler, 300)
);
generatePaginationLinks();
