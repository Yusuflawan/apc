const baseUrl = "http://localhost/bilal/backend";
// const baseUrl = "https://bilal.mssolarandelectronics.com/backend";
window.baseUrl = baseUrl;

function forceLogout() {
  if (window.AuthSession && typeof window.AuthSession.clearAuthSession === 'function') {
    window.AuthSession.clearAuthSession();
  } else {
    localStorage.removeItem("token");
    localStorage.removeItem("refresh_token");
    localStorage.removeItem("user");
  }
  window.location.href = "../index.html";
}

function ensureAuthenticated() {
  const token = localStorage.getItem("token");

  if (!token) {
    forceLogout();
    return false;
  }

  if (window.AuthSession && typeof window.AuthSession.isTokenExpired === 'function' && window.AuthSession.isTokenExpired(token)) {
    if (navigator.onLine) {
      window.AuthSession.refreshSession().catch(() => forceLogout());
      return true;
    }
    forceLogout();
    return false;
  }

  return true;
}

document.addEventListener("DOMContentLoaded", function () {
  if (!ensureAuthenticated()) return;

  // get user name from localStorage
  const user = JSON.parse(localStorage.getItem("user")) || {};
  const userName = user.name || "User";
  document.getElementById("user-name").textContent = userName;
});

const currentPage = window.location.pathname.split("/").pop();

document.getElementById("nav").innerHTML = `<nav class="navbar fixed-top">
      <div class="d-flex align-items-center navbar-left">
        <a href="#" class="menu-button d-none d-md-block"
          ><svg class="main" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 9 17">
            <rect x="0.48" y="0.5" width="7" height="1" />
            <rect x="0.48" y="7.5" width="7" height="1" />
            <rect x="0.48" y="15.5" width="7" height="1" />
          </svg>
          <svg class="sub" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 17">
            <rect x="1.56" y="0.5" width="16" height="1" />
            <rect x="1.56" y="7.5" width="16" height="1" />
            <rect x="1.56" y="15.5" width="16" height="1" />
          </svg> </a
        ><a href="#" class="menu-button-mobile d-xs-block d-sm-block d-md-none"
          ><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26 17">
            <rect x="0.5" y="0.5" width="25" height="1" />
            <rect x="0.5" y="7.5" width="25" height="1" />
            <rect x="0.5" y="15.5" width="25" height="1" /></svg
        ></a>
        <div class="" style="margin-left: 10px;">
        Bilal MS Solar and Electronics
        </div>
      </div>
      <div class="navbar-right">
        <div class="user d-inline-block">
          <button class="btn btn-empty p-0" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="name" id="user-name"></span> <span><img alt="Profile Picture" src="../assets/img/profiles/user-image.jpg" /></span>
          </button>
          <div class="dropdown-menu dropdown-menu-right mt-3">
            <a class="dropdown-item" href="account.html">Account</a>
            <a class="dropdown-item" href="#">Logout</a>
          </div>
        </div>
      </div>
    </nav>
 
    <div class="menu">
        <div class="main-menu">
            <div class="scroll">
            <ul class="list-unstyled">
              <li class="${currentPage === 'dashboard.html' || currentPage === '' ? 'active' : ''}">
                  <a href="dashboard.html"><i class="fas fa-gauge"></i> <span>Dashboards</span></a>
              </li>
              <li class="${currentPage === 'record-sales.html' ? 'active' : ''}">
                  <a href="record-sales.html"><i class="fas fa-shopping-cart"></i> Sales</a>
              </li>
              <li class="${currentPage === 'cashiers.html' ? 'active' : ''}">
                  <a href="cashiers.html"><i class="fas fa-users"></i> Cashiers</a>
              </li>
              <li class="${currentPage === 'inventory.html' ? 'active' : ''}">
                  <a href="inventory.html"><i class="fas fa-cash-register"></i> Inventory</a>
              </li>
              <li class="${currentPage === 'category.html' ? 'active' : ''}">
                <a href="category.html"><i class="fas fa-tags"></i> Category</a>
              </li>
              <li class="${currentPage === 'reports.html' ? 'active' : ''}">
                  <a href="reports.html"><i class="fas fa-file-alt"></i> Reports</a>
              </li>
          </ul>
            </div>
        </div>
     
        </div>
      </div>
    </div>
`;

// logout
document.querySelector(".user .dropdown-item:last-child").addEventListener("click", function () {
  forceLogout();
});


// if (response.status === 401 || response.status === 403) {
//   forceLogout();
//   return;
// }