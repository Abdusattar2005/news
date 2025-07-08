@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <!-- Форма поиска и фильтрации -->
            <div class="search-form">
                <div class="row">
                    <div class="col-md-4">
                        <label for="dateFilter" class="form-label">Фильтр по дате:</label>
                        <input type="date" id="dateFilter" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="searchInput" class="form-label">Поиск по заголовкам:</label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Введите текст для поиска...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button id="searchBtn" class="btn btn-primary">Поиск</button>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button id="resetBtn" class="btn btn-outline-secondary">Сбросить фильтры</button>
                        <button id="loadNewsBtn" class="btn btn-success ms-2">Загрузить новости</button>
                    </div>
                </div>
            </div>

            <!-- Индикатор загрузки -->
            <div id="loading" class="loading" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <p class="mt-2">Загрузка новостей...</p>
            </div>

            <!-- Результаты поиска -->
            <div id="searchResults" class="mb-3" style="display: none;">
                <div class="alert alert-info">
                    <strong>Результаты поиска:</strong> <span id="searchQuery"></span>
                    <span id="searchDate"></span>
                </div>
            </div>

            <!-- Список новостей -->
            <div id="newsList" class="row">
                <!-- Новости будут загружены здесь -->
            </div>

            <!-- Сообщение об отсутствии результатов -->
            <div id="noResults" class="no-results" style="display: none;">
                <h3>Новости не найдены</h3>
                <p>Попробуйте изменить параметры поиска или выбрать другую дату</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        class NewsApp {
            constructor() {
                this.initializeElements();
                this.attachEventListeners();
                this.loadNews();
            }

            initializeElements() {
                this.dateFilter = document.getElementById('dateFilter');
                this.searchInput = document.getElementById('searchInput');
                this.searchBtn = document.getElementById('searchBtn');
                this.resetBtn = document.getElementById('resetBtn');
                this.loadNewsBtn = document.getElementById('loadNewsBtn');
                this.loading = document.getElementById('loading');
                this.newsList = document.getElementById('newsList');
                this.noResults = document.getElementById('noResults');
                this.searchResults = document.getElementById('searchResults');
                this.searchQuery = document.getElementById('searchQuery');
                this.searchDate = document.getElementById('searchDate');
            }

            attachEventListeners() {
                this.searchBtn.addEventListener('click', () => this.searchNews());
                this.resetBtn.addEventListener('click', () => this.resetFilters());
                this.loadNewsBtn.addEventListener('click', () => this.loadNews());
                this.dateFilter.addEventListener('change', () => this.loadNews());

                // Поиск по Enter
                this.searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.searchNews();
                    }
                });
            }

            showLoading() {
                this.loading.style.display = 'block';
                this.newsList.innerHTML = '';
                this.noResults.style.display = 'none';
                this.searchResults.style.display = 'none';
            }

            hideLoading() {
                this.loading.style.display = 'none';
            }

            async loadNews() {
                this.showLoading();

                try {
                    const date = this.dateFilter.value;
                    const response = await axios.get('/api/news', {
                        params: { date }
                    });

                    if (response.data.success && response.data.data.length > 0) {
                        this.displayNews(response.data.data);
                    } else {
                        this.showNoResults();
                    }
                } catch (error) {
                    console.error('Ошибка загрузки новостей:', error);
                    this.showError('Ошибка при загрузке новостей. Попробуйте позже.');
                } finally {
                    this.hideLoading();
                }
            }

            async searchNews() {
                const query = this.searchInput.value.trim();
                if (!query) {
                    alert('Введите текст для поиска');
                    return;
                }

                this.showLoading();

                try {
                    const date = this.dateFilter.value;
                    const response = await axios.get('/api/news/search', {
                        params: {
                            query: query,
                            date: date
                        }
                    });

                    if (response.data.success) {
                        this.displayNews(response.data.data);
                        this.showSearchResults(query, date);
                    } else {
                        this.showNoResults();
                    }
                } catch (error) {
                    console.error('Ошибка поиска:', error);
                    this.showError('Ошибка при поиске новостей. Попробуйте позже.');
                } finally {
                    this.hideLoading();
                }
            }

            displayNews(news) {
                if (!news || news.length === 0) {
                    this.showNoResults();
                    return;
                }

                const newsHtml = news.map(item => this.createNewsCard(item)).join('');
                this.newsList.innerHTML = newsHtml;
                this.noResults.style.display = 'none';
            }

            createNewsCard(news) {
                const imageHtml = news.image
                    ? `<img src="${news.image}" class="card-img-top news-image" alt="${news.title}" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">`
                    : `<div class="card-img-top news-image bg-light d-flex align-items-center justify-content-center">
                          <span class="text-muted">Нет изображения</span>
                       </div>`;

                return `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card news-card h-100">
                            ${imageHtml}
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">${news.title}</h5>
                                <div class="mt-auto">
                                    <small class="text-muted">${news.date}</small>
                                    <div class="mt-2">
                                        <a href="${news.url}" target="_blank" class="btn btn-primary btn-sm">Читать полностью</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            showNoResults() {
                this.newsList.innerHTML = '';
                this.noResults.style.display = 'block';
                this.searchResults.style.display = 'none';
            }

            showSearchResults(query, date) {
                this.searchQuery.textContent = `"${query}"`;
                this.searchDate.textContent = date ? ` за ${date}` : '';
                this.searchResults.style.display = 'block';
            }

            showError(message) {
                this.newsList.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger" role="alert">
                            ${message}
                        </div>
                    </div>
                `;
            }

            resetFilters() {
                this.dateFilter.value = '{{ date('Y-m-d') }}';
                this.searchInput.value = '';
                this.searchResults.style.display = 'none';
                this.loadNews();
            }
        }

        // Инициализация приложения
        document.addEventListener('DOMContentLoaded', () => {
            new NewsApp();
        });
    </script>
@endpush
