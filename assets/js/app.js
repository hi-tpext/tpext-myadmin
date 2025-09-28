// Vue 应用主脚本
import { createApp, ref, computed, onMounted, h, nextTick } from 'vue';
import { darkTheme } from 'naive-ui';
import naive from 'naive-ui';

// 创建Vue应用
const createMainApp = () => {
    return createApp({
        setup() {
            // 移除loadingBar引用，使用自定义加载进度
            const loadingProgress = ref(0);
            const isLoading = ref(false);

            // 从 localStorage 读取保存的主题设置
            const savedTheme = localStorage.getItem('site_theme');
            const isDarkMode = ref(savedTheme === 'dark');
            const theme = ref(savedTheme === 'dark' ? darkTheme : null);

            // 主题色管理
            const savedThemeColor = localStorage.getItem('theme_color');
            const currentThemeColor = ref(savedThemeColor || 'purple');

            // 主题色配置
            const themeColors = ref([
                { key: 'purple', name: '默认', primary: '#6366f1', secondary: '#818cf8' },
                { key: 'blue', name: '蓝色', primary: '#317AF7', secondary: '#74ADF7' },
                { key: 'green', name: '绿色', primary: '#7cb342', secondary: '#8bc34a' },
                { key: 'red', name: '红色', primary: '#f5222d', secondary: '#ff4d4f' },
                { key: 'orange', name: '橙色', primary: '#fa8c16', secondary: '#ffa940' },
                { key: 'cyan', name: '青色', primary: '#13c2c2', secondary: '#36cfc9' },
                { key: 'pink', name: '粉色', primary: '#eb2f96', secondary: '#f759ab' },
                { key: 'yellow', name: '黄色', primary: '#fadb14', secondary: '#ffec3d' }
            ]);

            // 布局模式管理
            const savedLayout = localStorage.getItem('layout_mode');
            const layoutMode = ref(savedLayout || 'sidebar'); // 'sidebar' | 'top-nav'

            const collapsed = ref(false);
            const isSmallScreen = ref(false); // 新增：小屏幕检测
            const userManuallyToggled = ref(false); // 新增：用户手动切换标记
            const activeKey = ref('menu-1'); // 默认选中首页
            const currentPage = ref({ breadcrumb: ['首页'] });
            const menuOptions = ref([]);
            const loading = ref(true);

            // Tab页签管理
            const openTabs = ref([]);
            const activeTabKey = ref('menu-1'); // 默认激活首页Tab

            // 设置面板状态
            const showSettingsDrawer = ref(false);

            // 主题覆盖配置
            const themeOverrides = ref({});

            // 右键菜单状态
            const showTabContextMenu = ref(false);
            const tabContextMenuX = ref(0);
            const tabContextMenuY = ref(0);
            const currentContextTab = ref(null);

            // 右键菜单选项
            const tabContextMenuOptions = ref([
                {
                    label: '刷新页面',
                    key: 'refresh',
                    icon: () => h('i', { class: 'mdi mdi-refresh' }),
                },
                {
                    label: '关闭页面',
                    key: 'close',
                    icon: () => h('i', { class: 'mdi mdi-minus' }),
                },
                {
                    label: '关闭其他',
                    key: 'close-other',
                    icon: () => h('i', { class: 'mdi mdi-close' }),
                },
                {
                    label: '关闭所有',
                    key: 'close-all',
                    icon: () => h('i', { class: 'mdi mdi-close-outline' }),
                }
            ]);

            // 生成主题覆盖配置
            const generateThemeOverrides = (colorConfig) => {
                return {
                    common: {
                        primaryColor: colorConfig.primary,
                        primaryColorHover: colorConfig.secondary,
                        primaryColorPressed: colorConfig.primary,
                        primaryColorSuppl: colorConfig.secondary
                    },
                    Layout: {
                        siderColor: '#2f3447', // 左侧菜单背景始终暗黑
                        siderColorInverted: '#2f3447'
                    },
                    Menu: {
                        itemColorActive: colorConfig.primary + '1A', // 10% 透明度
                        itemColorActiveHover: colorConfig.primary + '26', // 15% 透明度
                        itemTextColorActive: colorConfig.primary,
                        itemTextColorActiveHover: colorConfig.primary,
                        itemTextColorChildActive: colorConfig.primary,
                        itemIconColorActive: colorConfig.primary,
                        itemIconColorActiveHover: colorConfig.primary,
                        itemIconColorChildActive: colorConfig.primary,
                        borderRadius: '6px'
                        // 移除全局菜单颜色覆盖，让CSS处理不同区域的菜单样式
                    },
                    Tabs: {
                        tabTextColorActiveBar: colorConfig.primary,
                        tabTextColorHoverBar: colorConfig.secondary,
                        barColor: colorConfig.primary
                    },
                    Button: {
                        colorPrimary: colorConfig.primary,
                        colorHoverPrimary: colorConfig.secondary,
                        colorPressedPrimary: colorConfig.primary,
                        borderPrimary: colorConfig.primary,
                        borderHoverPrimary: colorConfig.secondary,
                        borderPressedPrimary: colorConfig.primary
                    },
                    Switch: {
                        railColorActive: colorConfig.primary
                    },
                    Radio: {
                        dotColorActive: colorConfig.primary,
                        buttonColorActive: colorConfig.primary + '1A',
                        buttonTextColorActive: colorConfig.primary
                    },
                    Breadcrumb: {
                        itemTextColorActive: colorConfig.primary
                    }
                };
            };

            // 响应式检测函数
            const checkScreenSize = () => {
                const width = window.innerWidth;
                const wasSmallScreen = isSmallScreen.value;
                isSmallScreen.value = width <= 768;

                // 如果是第一次检测或用户没有手动切换过，则自动调整
                if (!userManuallyToggled.value) {
                    if (isSmallScreen.value && !collapsed.value) {
                        collapsed.value = true;
                    } else if (!isSmallScreen.value && wasSmallScreen && collapsed.value) {
                        collapsed.value = false;
                    }
                }
            };

            // 手动切换菜单的函数
            const toggleMenu = () => {
                collapsed.value = !collapsed.value;
                userManuallyToggled.value = true;
                // 3秒后重置手动切换标记，允许自动响应
                setTimeout(() => {
                    userManuallyToggled.value = false;
                }, 3000);
            };

            // 计算当前Tab的面包屑
            const currentTabBreadcrumb = computed(() => {
                const currentTab = openTabs.value.find(tab => tab.key === activeTabKey.value);
                return currentTab ? currentTab.breadcrumb || [] : [];
            });

            // 一级菜单项（用于顶部导航）
            const topMenuOptions = computed(() => {
                return menuOptions.value.filter(x => !x.is_home).map(item => ({
                    ...item,
                    children: undefined // 顶部菜单不显示子菜单
                }));
            });

            // 当前选中的一级菜单的二级菜单
            const sideMenuOptions = computed(() => {
                if (layoutMode.value !== 'top-nav') {
                    return menuOptions.value;
                }

                // 查找当前activeKey对应的顶级菜单
                let currentTopMenuKey = null;

                // 首先检查activeKey是否是一级菜单
                const directMatch = menuOptions.value.find(item => item.key === activeKey.value);
                if (directMatch) {
                    currentTopMenuKey = directMatch.key;
                } else {
                    // 如果不是，查找包含当前activeKey的一级菜单
                    for (const item of menuOptions.value) {
                        if (item.children) {
                            const hasChild = item.children.some(child => {
                                if (child.key === activeKey.value) return true;
                                if (child.children) {
                                    return child.children.some(grandChild => grandChild.key === activeKey.value);
                                }
                                return false;
                            });
                            if (hasChild) {
                                currentTopMenuKey = item.key;
                                break;
                            }
                        }
                    }
                }

                const currentTopMenu = menuOptions.value.find(item => item.key === currentTopMenuKey);
                return currentTopMenu?.children || [currentTopMenu];
            });

            // 标签页右键菜单处理
            const handleTabContextMenu = (event) => {
                // 阻止默认右键菜单
                event.preventDefault();

                // 通过activeTabKey获取当前激活的tab
                const currentTab = openTabs.value.find(tab => tab.key === activeTabKey.value);
                if (!currentTab) return;

                // 设置当前上下文标签页
                currentContextTab.value = currentTab;

                // 设置菜单位置
                tabContextMenuX.value = event.clientX;
                tabContextMenuY.value = event.clientY;

                // 显示菜单
                showTabContextMenu.value = true;
            };

            // 标签页右键菜单选项选择处理
            const handleTabContextSelect = (key) => {
                // 隐藏菜单
                showTabContextMenu.value = false;

                // 根据选择的选项执行相应操作
                switch (key) {
                    case 'refresh':
                        refreshTab(currentContextTab.value.key);
                        break;
                    case 'close':
                        if (currentContextTab.value.closable) {
                            handleTabClose(currentContextTab.value.key);
                        }
                        break;
                    case 'close-other':
                        closeOtherTabs(currentContextTab.value.key);
                        break;
                    case 'close-all':
                        closeAllTabs();
                        break;
                }
            };

            // 刷新标签页
            const refreshTab = (key) => {
                // 查找要刷新的标签页
                const tab = openTabs.value.find(t => t.key === key);
                if (tab) {
                    // 显示加载进度
                    showLoadingProgress();

                    // 监听iframe加载完成
                    const iframe = document.querySelector(`iframe[src="${tab.url}"]`);
                    if (iframe) {
                        const onLoad = () => {
                            hideLoadingProgress();
                            iframe.removeEventListener('load', onLoad);
                        };

                        const onError = () => {
                            hideLoadingProgress();
                            iframe.removeEventListener('error', onError);
                        };

                        iframe.addEventListener('load', onLoad);
                        iframe.addEventListener('error', onError);

                        iframe.src = iframe.src; // 重新加载iframe
                    }
                }
            };

            // 关闭其他标签页
            const closeOtherTabs = (key) => {
                // 保留当前标签页和首页标签页
                const homeTab = openTabs.value.find(tab => tab.is_home);
                const currentTab = openTabs.value.find(tab => tab.key === key);

                if (currentTab) {
                    const newTabs = [];
                    if (homeTab) {
                        newTabs.push(homeTab);
                    }
                    if (currentTab.key !== homeTab?.key) {
                        newTabs.push(currentTab);
                    }

                    openTabs.value = newTabs;
                    activeTabKey.value = key;
                }
            };

            // 关闭所有标签页
            const closeAllTabs = () => {
                // 保留首页标签页
                const homeTab = openTabs.value.find(tab => tab.is_home);

                if (homeTab) {
                    openTabs.value = [homeTab];
                    activeTabKey.value = homeTab.key;
                } else {
                    openTabs.value = [];
                    activeTabKey.value = '';
                }
            };

            // Tabs滚动功能
            const scrollTabsLeft = () => {
                // 使用更准确的选择器查找tabs容器
                const tabsContainer = document.querySelector('.n-tabs-nav-scroll-wrapper .v-x-scroll');
                if (tabsContainer) {
                    // 增加滚动距离以确保可见效果
                    tabsContainer.scrollBy({ left: -300, behavior: 'smooth' });
                }
            };

            const scrollTabsRight = () => {
                // 使用更准确的选择器查找tabs容器
                const tabsContainer = document.querySelector('.n-tabs-nav-scroll-wrapper .v-x-scroll');
                if (tabsContainer) {
                    // 增加滚动距离以确保可见效果
                    tabsContainer.scrollBy({ left: 300, behavior: 'smooth' });
                }
            };

            // 显示加载进度
            const showLoadingProgress = () => {
                isLoading.value = true;
                loadingProgress.value = 0;
                const loadingIndicator = document.getElementById('loading-indicator');
                if (loadingIndicator) {
                    loadingIndicator.classList.remove('hidden');
                }

                // 模拟进度增加
                const interval = setInterval(() => {
                    if (loadingProgress.value < 90) {
                        loadingProgress.value += 5;
                    } else {
                        clearInterval(interval);
                        setTimeout(() => {
                            hideLoadingProgress();
                        }, 2000);
                    }
                }, 100);
            };

            // 隐藏加载进度
            const hideLoadingProgress = () => {
                loadingProgress.value = 100;
                setTimeout(() => {
                    isLoading.value = false;
                    const loadingIndicator = document.getElementById('loading-indicator');
                    if (loadingIndicator) {
                        loadingIndicator.classList.add('hidden');
                    }
                }, 300);
            };

            onMounted(async () => {
                document.getElementById('app').classList.remove('hidden');
                document.getElementById('page-loader').style.display = 'none';
                // 初始化响应式检测
                checkScreenSize();

                // 监听窗口大小变化
                window.addEventListener('resize', checkScreenSize);

                try {
                    const routeConfig = await RouteManager.loadRoutes();
                    if (routeConfig) {
                        menuOptions.value = RouteManager.getMenuOptions();
                        currentPage.value = RouteManager.getPageConfig('menu-1');

                        // 初始化默认的首页Tab
                        const homePage = routeConfig.routes.find(route => route.is_home === 1) || routeConfig.routes[0];
                        if (homePage) {
                            openTabs.value = [{
                                key: homePage.id,
                                title: homePage.name,
                                url: homePage.url,
                                breadcrumb: homePage.breadcrumb,
                                closable: false,
                                is_home: homePage.is_home === 1,
                                is_out: homePage.is_out === 1
                            }];
                            activeTabKey.value = homePage.id;
                            activeKey.value = homePage.id;
                        }
                    }

                    // 初始化主题设置，确保暗黑模式正确应用
                    if (isDarkMode.value) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }

                    // 初始化主题色设置
                    const colorConfig = themeColors.value.find(c => c.key === currentThemeColor.value);
                    if (colorConfig) {
                        handleThemeColorChange(currentThemeColor.value);
                    }

                    // 如果是顶部导航模式，确保默认选中的菜单项正确
                    if (layoutMode.value === 'top-nav') {
                        // 检查当前的activeKey是否有效，如果不有效则设置为第一个可用的页面
                        const pageExists = RouteManager.getPageConfig(activeKey.value);
                        if (!pageExists || !pageExists.breadcrumb) {
                            // 查找第一个有效的页面
                            for (const item of menuOptions.value) {
                                if (item.children && item.children.length > 0) {
                                    const firstChild = item.children[0];
                                    if (firstChild.children && firstChild.children.length > 0) {
                                        const targetKey = firstChild.children[0].key;
                                        activeKey.value = targetKey;

                                        // 创建对应的Tab页面
                                        const targetPageConfig = RouteManager.getPageConfig(targetKey);
                                        if (targetPageConfig) {
                                            const existingTab = openTabs.value.find(tab => tab.key === targetKey);
                                            if (!existingTab) {
                                                const newTab = {
                                                    key: targetKey,
                                                    title: getMenuTitle(targetKey),
                                                    url: targetPageConfig.url,
                                                    breadcrumb: targetPageConfig.breadcrumb,
                                                    closable: !targetPageConfig.is_home,
                                                    is_home: targetPageConfig.is_home === 1,
                                                    is_out: targetPageConfig.is_out === 1
                                                };
                                                openTabs.value.push(newTab);
                                                handleTabAdd(targetKey);
                                            }
                                            activeTabKey.value = targetKey;
                                        }
                                        break;
                                    } else {
                                        const targetKey = firstChild.key;
                                        activeKey.value = targetKey;

                                        // 创建对应的Tab页面
                                        const targetPageConfig = RouteManager.getPageConfig(targetKey);
                                        if (targetPageConfig) {
                                            const existingTab = openTabs.value.find(tab => tab.key === targetKey);
                                            if (!existingTab) {
                                                const newTab = {
                                                    key: targetKey,
                                                    title: getMenuTitle(targetKey),
                                                    url: targetPageConfig.url,
                                                    breadcrumb: targetPageConfig.breadcrumb,
                                                    closable: !targetPageConfig.is_home,
                                                    is_home: targetPageConfig.is_home === 1,
                                                    is_out: targetPageConfig.is_out === 1
                                                };
                                                openTabs.value.push(newTab);
                                                handleTabAdd(targetKey);
                                            }
                                            activeTabKey.value = targetKey;
                                        }
                                        break;
                                    }
                                }
                            }
                        } else {
                            // 当前菜单项有效，确保有对应的Tab页面
                            const newTab = {
                                key: activeKey.value,
                                title: getMenuTitle(activeKey.value),
                                url: pageExists.url,
                                breadcrumb: pageExists.breadcrumb,
                                closable: !pageExists.is_home,
                                is_home: pageExists.is_home === 1,
                                is_out: pageExists.is_out === 1
                            };
                            // 清空原有Tab并添加新Tab
                            openTabs.value = [newTab];
                            activeTabKey.value = activeKey.value;
                        }
                    }
                } catch (error) {
                    console.error('加载路由配置失败:', error);
                } finally {
                    loading.value = false;
                }
            });

            const userOptions = [
                {
                    label: '个人设置',
                    key: 'profile'
                },
                {
                    label: '修改密码',
                    key: 'change_pwd'
                },
                {
                    label: '退出登录',
                    key: 'logout'
                }
            ];

            const handleThemeChange = (value) => {
                isDarkMode.value = value;
                theme.value = value ? darkTheme : null;

                // 同时控制Tailwind CSS的暗黑模式
                if (value) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }

                // 保存主题设置到 localStorage
                localStorage.setItem('site_theme', value ? 'dark' : 'light');

                let iframes = document.querySelectorAll('iframe');
                for (let i = 0; i < iframes.length; i += 1) {
                    let iframe = iframes[i];
                    if (iframe.contentWindow) {
                        iframe.contentWindow.postMessage({
                            type: 'change_theme',
                            theme: value ? 'dark' : 'light'
                        }, '*');
                    }
                }
            };

            // 主题色切换处理
            const handleThemeColorChange = (colorKey) => {
                currentThemeColor.value = colorKey;
                const colorConfig = themeColors.value.find(c => c.key === colorKey);

                if (colorConfig) {
                    // 应用CSS变量到页面
                    const root = document.documentElement;
                    root.style.setProperty('--primary-color', colorConfig.primary);
                    root.style.setProperty('--primary-color-hover', colorConfig.secondary);
                    root.style.setProperty('--primary-color-light', colorConfig.primary + '1A');
                    root.style.setProperty('--primary-color-lighter', colorConfig.primary + '0D');

                    // 更新主题覆盖配置
                    themeOverrides.value = generateThemeOverrides(colorConfig);
                    // 保存到localStorage
                    localStorage.setItem('theme_color', colorKey);

                    let iframes = document.querySelectorAll('iframe');
                    for (let i = 0; i < iframes.length; i += 1) {
                        let iframe = iframes[i];
                        if (iframe.contentWindow) {
                            iframe.contentWindow.postMessage({
                                type: 'change_theme_color',
                                color: colorConfig.primary
                            }, '*');
                        }
                    }

                    // 更新Logo等元素的渐变色彩
                    updateThemeElements(colorConfig);
                    // 更新进度条颜色
                    updateProgressBarColor(colorConfig);
                }
            };

            // 更新主题元素
            const updateThemeElements = (colorConfig) => {
                // 更新Logo文字渐变
                const logoTexts = document.querySelectorAll('.logo-text');
                logoTexts.forEach(el => {
                    el.style.background = `linear-gradient(135deg, ${colorConfig.primary} 0%, ${colorConfig.secondary} 100%)`;
                    el.style.webkitBackgroundClip = 'text';
                    el.style.webkitTextFillColor = 'transparent';
                    el.style.backgroundClip = 'text';
                });
            };

            // 更新进度条颜色
            const updateProgressBarColor = (colorConfig) => {
                // 更新CSS变量
                const root = document.documentElement;
                root.style.setProperty('--primary-color', colorConfig.primary);

                // 直接更新进度条元素的颜色（如果存在）
                const progressBar = document.querySelector('.loading-indicator .n-progress-fill');
                if (progressBar) {
                    progressBar.style.backgroundColor = colorConfig.primary;
                }
            };

            // 布局模式切换
            const handleLayoutChange = (mode) => {
                layoutMode.value = mode;
                localStorage.setItem('layout_mode', mode);
            };

            const handleMenuSelect = (key) => {
                activeKey.value = key;

                // 在顶部导航模式下，如果点击的是一级菜单且有子菜单，不创建Tab页面
                if (layoutMode.value === 'top-nav') {
                    const menuItem = menuOptions.value.find(item => item.key === key);
                    if (menuItem && menuItem.children && menuItem.children.length > 0) {
                        // 如果是一级菜单且有子菜单，不执行后续的Tab创建逻辑
                        return;
                    }
                }

                // 重新获取页面配置
                const finalPageConfig = RouteManager.getPageConfig(key);

                // 如果没有页面配置，说明这是一个父级菜单，不创建Tab
                if (!finalPageConfig) {
                    return;
                }

                // 处理外链：在新窗口打开
                if (finalPageConfig.is_out === 1) {
                    window.open(finalPageConfig.url, '_blank');
                    return;
                }

                // 检查是否已经打开该页面
                const existingTab = openTabs.value.find(tab => tab.key === key);
                if (existingTab) {
                    // 如果已经打开，就切换到该Tab
                    const oldKey = activeTabKey.value;
                    activeTabKey.value = key;
                    window.dispatchEvent(new CustomEvent('tab-change', {
                        detail: { oldKey: oldKey, newKey: key }
                    }));

                } else {
                    // 显示加载进度
                    showLoadingProgress();

                    // 创建新的Tab页面
                    const newTab = {
                        key: key,
                        title: getMenuTitle(key),
                        url: finalPageConfig.url,
                        breadcrumb: finalPageConfig.breadcrumb,
                        closable: !finalPageConfig.is_home, // 首页页面不可关闭
                        is_home: finalPageConfig.is_home === 1,
                        is_out: finalPageConfig.is_out === 1
                    };

                    openTabs.value.push(newTab);
                    handleTabAdd(key);
                    const oldKey = activeTabKey.value;
                    activeTabKey.value = key;

                    setTimeout(() => {
                        window.dispatchEvent(new CustomEvent('tab-change', {
                            detail: { oldKey: oldKey, newKey: key }
                        }));
                    }, 500);
                }
            };

            const openUrl = (url, title) => {
                const menuItem = window.menuJson.find(item => item.url === url);
                if (menuItem) {
                    handleMenuSelect(`menu-${menuItem.id}`);
                }
                else {
                    console.log('url not found' + url)
                    let key = 'menu-' + url.replace(/\W/, '_');
                    // 检查是否已经打开该页面
                    const existingTab = openTabs.value.find(tab => tab.key === key);
                    if (existingTab) {
                        // 如果已经打开，就切换到该Tab
                        activeTabKey.value = key;
                    } else {
                        // 显示加载进度
                        showLoadingProgress();

                        // 创建新的Tab页面
                        const newTab = {
                            key: key,
                            title: title,
                            url: url,
                            breadcrumb: [title],
                            closable: true, // 首页页面不可关闭
                            is_home: false,
                            is_out: false
                        };

                        openTabs.value.push(newTab);
                        handleTabAdd(key);
                        activeTabKey.value = key;
                    }
                }
            }

            // iframe加载处理
            const handleIframeLoad = (key) => {
                const tab = openTabs.value.find(t => t.key === key);
                if (tab) {
                    // 隐藏加载进度
                    hideLoadingProgress();
                }
            };

            // iframe加载错误处理
            const handleIframeError = (key) => {
                const tab = openTabs.value.find(t => t.key === key);
                if (tab) {
                    // 隐藏加载进度
                    hideLoadingProgress();
                }
            };

            // 获取菜单标题
            const getMenuTitle = (key) => {
                const findInMenu = (items) => {
                    for (const item of items) {
                        if (item.key === key) {
                            return item.label;
                        }
                        if (item.children) {
                            const found = findInMenu(item.children);
                            if (found) return found;
                        }
                    }
                    return null;
                };

                return findInMenu(menuOptions.value) || key;
            };

            // Tab关闭处理
            const handleTabClose = (key) => {
                const index = openTabs.value.findIndex(tab => tab.key === key);
                if (index === -1) return;

                // 首页页面不可关闭
                if (openTabs.value[index].is_home) {
                    return;
                }

                openTabs.value.splice(index, 1);

                // 如果关闭的是当前Tab，切换到其他Tab
                if (activeTabKey.value === key) {
                    if (openTabs.value.length > 0) {
                        // 切换到相邻的Tab
                        const newIndex = Math.min(index, openTabs.value.length - 1);
                        activeTabKey.value = openTabs.value[newIndex].key;
                    } else {
                        activeTabKey.value = '';
                    }
                }
            };

            const handleTabAdd = (key) => {
                setTimeout(() => {
                    const tabsContainer = document.querySelector('.n-tabs-nav-scroll-wrapper .v-x-scroll');
                    tabsContainer.scrollBy({
                        left: 1000,
                        behavior: 'smooth'
                    });
                }, 100);
            };

            const handleTabLeave = (name, oldName) => {
                // 这个方法在tab切换前调用，可以用来计算方向
                // 获取当前tab和目标tab的索引
                // 触发自定义事件，用于其他组件监听tab切换
                window.dispatchEvent(new CustomEvent('tab-change', {
                    detail: { oldKey: oldName, newKey: name }
                }));

                const currentIndex = openTabs.value.findIndex(tab => tab.key === oldName);
                const targetIndex = openTabs.value.findIndex(tab => tab.key === name);
                if (currentIndex !== -1 && targetIndex !== -1) {
                    // 计算需要滚动的距离
                    var diff = 100 * (targetIndex - currentIndex);//判断是往左点还是往右点，加上惯性
                    if (targetIndex == 0 || targetIndex == openTabs.value.length - 1) {
                        diff = 0;
                    }
                    if (diff == 0) {
                        return true;
                    }
                    const tabsContainer = document.querySelector('.n-tabs-nav-scroll-wrapper .v-x-scroll');
                    if (!tabsContainer) {
                        return;
                    }
                    setTimeout(() => {
                        tabsContainer.scrollBy({
                            left: diff,
                            behavior: 'smooth'
                        });
                    }, 100);
                }

                return true;
            };

            // Tab切换处理
            const handleTabChange = (key) => {
                activeTabKey.value = key;
                activeKey.value = key;
            };

            const handleUserSelect = (key) => {
                if (key === 'logout') {
                    layer.msg('确定要注销登录？', {
                        time: 4000,
                        btn: ['确定', '取消'],
                        yes: function (params) {
                            location.replace("/admin/index/logout");
                        }
                    });
                } else if (key === 'profile') {
                    openUrl('/admin/index/profile', '个人中心');
                }
                else if (key == 'change_pwd') {
                    openUrl('/admin/index/changepwd', '修改密码');
                }
            };

            // Tab双击刷新处理
            const handleTabDblClick = (event) => {
                // 防止事件冒泡
                event.stopPropagation();

                // 刷新当前激活的tab
                refreshTab(activeTabKey.value);
            };

            return {
                isDarkMode,
                theme,
                themeOverrides,
                collapsed,
                isSmallScreen,
                activeKey,
                currentPage,
                userOptions,
                loading,
                openTabs,
                activeTabKey,
                currentTabBreadcrumb,
                layoutMode,
                topMenuOptions,
                sideMenuOptions,
                themeColors,
                currentThemeColor,
                showSettingsDrawer,
                generateThemeOverrides,
                handleThemeChange,
                handleThemeColorChange,
                handleLayoutChange,
                handleMenuSelect,
                handleUserSelect,
                handleTabClose,
                openUrl,
                handleTabAdd,
                handleTabChange,
                handleTabLeave,
                updateThemeElements,
                updateProgressBarColor,
                getMenuTitle,
                toggleMenu,
                checkScreenSize,
                // iframe加载处理函数
                handleIframeLoad,
                handleIframeError,
                handleTabContextMenu,
                // 刷新标签页函数
                refreshTab,
                // 关闭标签页函数
                closeOtherTabs,
                closeAllTabs,
                // Tabs滚动函数
                scrollTabsLeft,
                scrollTabsRight,
                // 自定义加载进度相关
                loadingProgress,
                isLoading,
                // 右键菜单相关
                showTabContextMenu,
                tabContextMenuX,
                tabContextMenuY,
                currentContextTab,
                tabContextMenuOptions,
                handleTabContextSelect,
                handleTabDblClick,
            };
        }
    });
}

// 初始化应用
const app = createMainApp();
const vueObj = app.use(naive).mount('#app');

// 监听tab切换事件，添加页面切换动画
window.addEventListener('tab-change', (event) => {
    const { oldKey, newKey } = event.detail;

    // 为iframe添加切换动画
    const oldIframe = document.querySelector(`#tab-${oldKey}`);
    const newIframe = document.querySelector(`#tab-${newKey}`);

    // if (oldIframe) {
    //     oldIframe.style.opacity = '0';
    //     oldIframe.style.transition = 'opacity 0.5s ease';
    // }

    if (newIframe) {
        newIframe.style.opacity = '0';
        newIframe.style.transition = 'opacity 0.5s ease';

        // 强制重绘
        newIframe.offsetHeight;

        setTimeout(() => {
            newIframe.style.opacity = '1';
        }, 50);
    }
});

((function ($) {
    // 兼容 lightyear 的 MultiTabs js
    var MultiTabs = function (element) {
        var self = this;
        self.$element = $(element);
    };
    MultiTabs.prototype = {
        constructor: MultiTabs,
        /**
         * create tab and return this.
         * @param obj           the obj to trigger multitabs
         * @param active        if true, active tab after create
         * @returns this        Chain structure.
         */
        create: function (obj, active) {
            var url = $(obj).attr("href");
            if (!url || url == '#') {
                return false;
            }
            var text = $.trim($(obj).attr('title') || $(obj).data('title') || $(obj).text());
            vueObj.openUrl(url, text);
            return this;
        },
    };

    $.fn.multitabs = function () {
        var self = $(this),
            did = 'multitabs',
            multitabs = $(document).data(did);
        if (!multitabs) {
            multitabs = new MultiTabs(this);
            $(document).data(did, multitabs);
        }
        return $(document).data(did);
    };

    $('a.open-tab').click(function () {
        $.fn.multitabs().create(this, true);
        return false;
    });
})(jQuery));