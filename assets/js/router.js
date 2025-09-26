import { h } from 'vue';
import { NIcon } from 'naive-ui';

// 路由管理模块
const RouteManager = {
    routeConfig: null,
    menuData: null,

    // 加载路由配置
    async loadRoutes() {
        try {
            // 加载菜单数据
            this.menuData = window.menuJson;

            // 初始化路由配置对象
            this.routeConfig = {
                routes: [],
                menuOptions: []
            };

            // 根据菜单数据生成路由配置
            this.generateRouteConfigFromMenu();

            return this.routeConfig;
        } catch (error) {
            console.error('加载路由配置失败:', error);
            return null;
        }
    },

    // 根据菜单数据生成路由配置
    generateRouteConfigFromMenu() {
        if (!this.menuData || !this.routeConfig) return;

        // 构建菜单树结构
        const menuTree = this.buildMenuTree(this.menuData);

        // 生成菜单选项
        this.routeConfig.menuOptions = this.convertToMenuOptions(menuTree);

        // 生成路由配置
        this.routeConfig.routes = this.convertToRoutes(this.menuData, this.menuData);
    },

    // 构建菜单树结构
    buildMenuTree(menuData) {
        const menuMap = {};
        const rootMenus = [];

        // 初始化所有菜单项
        menuData.forEach(item => {
            menuMap[item.id] = { ...item, children: [] };
        });

        // 构建父子关系
        menuData.forEach(item => {
            if (item.pid === 0) {
                rootMenus.push(menuMap[item.id]);
            } else {
                const parent = menuMap[item.pid];
                if (parent) {
                    parent.children.push(menuMap[item.id]);
                }
            }
        });

        return rootMenus;
    },

    // 转换为菜单选项
    convertToMenuOptions(menuTree) {
        return menuTree.map(item => this.convertMenuItem(item)).filter(item => item !== null);
    },

    // 转换单个菜单项
    convertMenuItem(item) {
        // 如果是分隔符或无效菜单项，跳过
        if (item.url === '#' && (!item.children || item.children.length === 0)) {
            return null;
        }

        const menuItem = {
            label: item.name,
            key: `menu-${item.id}`,
            icon: item.icon,
            is_out: item.is_out,
            is_home: item.is_home,
        };

        // 处理子菜单
        if (item.children && item.children.length > 0) {
            const children = item.children.map(child => this.convertMenuItem(child)).filter(child => child !== null);
            if (children.length > 0) {
                menuItem.children = children;
            }
        }

        return menuItem;
    },

    // 转换为路由配置
    convertToRoutes(menuData, allMenuData) {
        // 创建菜单映射以便快速查找父级菜单
        const menuMap = {};
        allMenuData.forEach(item => {
            menuMap[item.id] = item;
        });

        return menuData.map(item => {
            // 构建面包屑路径
            const breadcrumb = this.buildBreadcrumb(item, menuMap);

            return {
                id: `menu-${item.id}`,
                name: item.name,
                icon: item.icon,
                url: item.url,
                is_out: item.is_out,
                is_home: item.is_home,
                breadcrumb: breadcrumb
            };
        });
    },

    // 构建面包屑路径
    buildBreadcrumb(item, menuMap) {
        const breadcrumb = [item.name];
        let current = item;

        // 向上遍历父级菜单，构建完整的面包屑路径
        while (current.pid !== 0) {
            const parent = menuMap[current.pid];
            if (parent) {
                breadcrumb.unshift(parent.name); // 将父级名称添加到面包屑开头
                current = parent;
            } else {
                break;
            }
        }

        return breadcrumb;
    },

    // 获取页面配置
    getPageConfig(key) {
        if (!this.routeConfig || !this.routeConfig.routes) {
            return {
                breadcrumb: ['页面']
            };
        }

        // 在routes数组中查找匹配的路由
        const route = this.routeConfig.routes.find(r => r.id === key);
        return route || {
            breadcrumb: ['页面']
        };
    },

    // 获取菜单选项
    getMenuOptions() {
        if (!this.routeConfig) return [];

        // 递归处理菜单项，添加图标
        const processMenuItems = (items) => {
            return items.map(item => {
                const processedItem = { ...item };

                // 如果有图标字符串，转换为图标组件
                if (item.icon && typeof item.icon === 'string') {
                    processedItem.icon = this.renderIcon(item.icon);
                }

                // 递归处理子菜单
                if (item.children) {
                    processedItem.children = processMenuItems(item.children);
                }

                return processedItem;
            });
        };

        return processMenuItems(this.routeConfig.menuOptions);
    },

    // 渲染图标
    renderIcon(iconName) {

        // 如果是 MDI 图标（以 mdi 开头）
        if (iconName && iconName.startsWith('mdi')) {
            // 处理 mdi mdi mdi-icon-name 格式
            // 提取实际的图标名称
            const iconClass = iconName.split(' ').pop(); // 获取最后一个类名
            const actualIconName = iconClass.startsWith('mdi-') ? iconClass : 'mdi-' + iconClass;

            return () => h(NIcon, null, {
                default: () => h('i', { class: `mdi ${actualIconName}` })
            });
        }

        return () => h(NIcon, null, {
            default: () => h('i', { class: 'mdi mdi-menu' })
        });
    }
};

// 导出到全局
window.RouteManager = RouteManager;