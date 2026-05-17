import React, {useState} from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  StatusBar,
  Alert,
  Platform,
} from 'react-native';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {launchImageLibrary, launchCamera} from 'react-native-image-picker';
import {pick, types, isErrorWithCode, errorCodes} from '@react-native-documents/picker';
import assignmentService from '../../services/assignmentService';
import {MainStackParamList} from '../../navigation/MainNavigator';

type AssignmentSubmitRouteProp = RouteProp<MainStackParamList, 'AssignmentSubmit'>;
type NavigationProp = NativeStackNavigationProp<MainStackParamList>;

interface SelectedFile {
  uri: string;
  name: string;
  type: string;
  size?: number;
}

const AssignmentSubmitScreen: React.FC = () => {
  const route = useRoute<AssignmentSubmitRouteProp>();
  const navigation = useNavigation<NavigationProp>();
  const {assignmentId, assignmentTitle, submissionType, maxScore} = route.params;

  const [files, setFiles] = useState<SelectedFile[]>([]);
  const [textContent, setTextContent] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const pickImage = async () => {
    try {
      const result = await launchImageLibrary({
        mediaType: 'photo',
        selectionLimit: 5,
        quality: 0.8,
      });

      if (result.assets && result.assets.length > 0) {
        const newFiles: SelectedFile[] = result.assets.map((asset: any) => ({
          uri: asset.uri || '',
          name: asset.fileName || `image_${Date.now()}.jpg`,
          type: asset.type || 'image/jpeg',
          size: asset.fileSize,
        }));
        setFiles(prev => [...prev, ...newFiles]);
      }
    } catch (error) {
      console.error('Error picking image:', error);
      Alert.alert('Lỗi', 'Không thể chọn ảnh');
    }
  };

  const takePhoto = async () => {
    try {
      const result = await launchCamera({
        mediaType: 'photo',
        quality: 0.8,
        saveToPhotos: false,
      });

      if (result.assets && result.assets.length > 0) {
        const asset = result.assets[0];
        setFiles(prev => [
          ...prev,
          {
            uri: asset.uri || '',
            name: asset.fileName || `photo_${Date.now()}.jpg`,
            type: asset.type || 'image/jpeg',
            size: asset.fileSize,
          },
        ]);
      }
    } catch (error) {
      console.error('Error taking photo:', error);
      Alert.alert('Lỗi', 'Không thể chụp ảnh');
    }
  };

  const pickDocument = async () => {
    try {
      const results = await pick({
        type: [
          types.pdf,
          types.doc,
          types.docx,
          types.images,
          types.plainText,
        ],
        allowMultiSelection: true,
      });

      const newFiles: SelectedFile[] = results.map((doc: any) => ({
        uri: doc.uri,
        name: doc.name || `file_${Date.now()}`,
        type: doc.type || 'application/octet-stream',
        size: doc.size || undefined,
      }));
      setFiles(prev => [...prev, ...newFiles]);
    } catch (error) {
      if (isErrorWithCode(error) && error.code === errorCodes.OPERATION_CANCELED) {
        return;
      }
      console.error('Error picking document:', error);
      Alert.alert('Lỗi', 'Không thể chọn tệp');
    }
  };

  const removeFile = (index: number) => {
    setFiles(prev => prev.filter((_, i) => i !== index));
  };

  const formatFileSize = (bytes?: number) => {
    if (!bytes) return '';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
  };

  const getFileIcon = (type: string) => {
    if (type.includes('image')) return '🖼️';
    if (type.includes('pdf')) return '📄';
    if (type.includes('word') || type.includes('document')) return '📝';
    return '📎';
  };

  const handleSubmit = async () => {
    if (files.length === 0 && !textContent.trim()) {
      Alert.alert('Thông báo', 'Vui lòng chọn tệp hoặc nhập nội dung bài làm');
      return;
    }

    Alert.alert('Xác nhận nộp bài', 'Bạn chỉ được nộp bài 1 lần. Bạn chắc chắn muốn nộp?', [
      {text: 'Huỷ', style: 'cancel'},
      {
        text: 'Nộp bài',
        style: 'default',
        onPress: async () => {
          setSubmitting(true);
          try {
            const response = await assignmentService.submitAssignment(
              assignmentId,
              files.map(f => ({uri: f.uri, name: f.name, type: f.type})),
              textContent.trim() || undefined,
            );

            if (response.success) {
              Alert.alert('Thành công', response.message || 'Nộp bài thành công!', [
                {
                  text: 'OK',
                  onPress: () => navigation.goBack(),
                },
              ]);
            } else {
              Alert.alert('Lỗi', (response as any).message || 'Không thể nộp bài');
            }
          } catch (error: any) {
            const message =
              error?.response?.data?.message || 'Đã xảy ra lỗi khi nộp bài';
            Alert.alert('Lỗi', message);
          } finally {
            setSubmitting(false);
          }
        },
      },
    ]);
  };

  const showFileOptions = () => {
    Alert.alert('Chọn tệp', 'Bạn muốn thêm tệp bằng cách nào?', [
      {text: 'Chụp ảnh', onPress: takePhoto},
      {text: 'Thư viện ảnh', onPress: pickImage},
      {text: 'Chọn tệp', onPress: pickDocument},
      {text: 'Huỷ', style: 'cancel'},
    ]);
  };

  const showTextInput = submissionType === 'text' || submissionType === 'both';
  const showFileInput = submissionType === 'file' || submissionType === 'both';

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0D47A1" />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>←</Text>
        </TouchableOpacity>
        <View style={styles.headerContent}>
          <Text style={styles.headerTitle}>Nộp bài tập</Text>
          <Text style={styles.headerSubtitle} numberOfLines={1}>
            {assignmentTitle}
          </Text>
        </View>
      </View>

      <ScrollView
        style={styles.content}
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled">
        {/* Info Banner */}
        <View style={styles.infoBanner}>
          <Text style={styles.infoBannerIcon}>ℹ️</Text>
          <View style={styles.infoBannerContent}>
            <Text style={styles.infoBannerTitle}>Lưu ý</Text>
            <Text style={styles.infoBannerText}>
              Bạn chỉ được nộp bài 1 lần. Hãy kiểm tra kỹ trước khi nộp.
              {'\n'}Điểm tối đa: {maxScore}
            </Text>
          </View>
        </View>

        {/* File Upload Section */}
        {showFileInput && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>📎 Tệp đính kèm</Text>
            <Text style={styles.cardSubtitle}>
              Chọn ảnh chụp bài làm hoặc tệp tài liệu
            </Text>

            {/* Selected Files */}
            {files.length > 0 && (
              <View style={styles.filesList}>
                {files.map((file, index) => (
                  <View key={index} style={styles.selectedFile}>
                    <Text style={styles.selectedFileIcon}>
                      {getFileIcon(file.type)}
                    </Text>
                    <View style={styles.selectedFileInfo}>
                      <Text style={styles.selectedFileName} numberOfLines={1}>
                        {file.name}
                      </Text>
                      {file.size && (
                        <Text style={styles.selectedFileSize}>
                          {formatFileSize(file.size)}
                        </Text>
                      )}
                    </View>
                    <TouchableOpacity
                      style={styles.removeFileButton}
                      onPress={() => removeFile(index)}>
                      <Text style={styles.removeFileIcon}>✕</Text>
                    </TouchableOpacity>
                  </View>
                ))}
              </View>
            )}

            {/* Add File Buttons */}
            <View style={styles.addFileButtons}>
              <TouchableOpacity
                style={styles.addFileButton}
                onPress={takePhoto}>
                <Text style={styles.addFileButtonIcon}>📷</Text>
                <Text style={styles.addFileButtonText}>Chụp ảnh</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.addFileButton}
                onPress={pickImage}>
                <Text style={styles.addFileButtonIcon}>🖼️</Text>
                <Text style={styles.addFileButtonText}>Thư viện</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.addFileButton}
                onPress={pickDocument}>
                <Text style={styles.addFileButtonIcon}>📄</Text>
                <Text style={styles.addFileButtonText}>Tài liệu</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}

        {/* Text Content Section */}
        {showTextInput && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>✍️ Nội dung bài làm</Text>
            <TextInput
              style={styles.textInput}
              multiline
              numberOfLines={10}
              placeholder="Nhập nội dung bài làm của bạn..."
              placeholderTextColor="#9CA3AF"
              value={textContent}
              onChangeText={setTextContent}
              textAlignVertical="top"
            />
            <Text style={styles.charCount}>
              {textContent.length} ký tự
            </Text>
          </View>
        )}

        <View style={styles.bottomSpace} />
      </ScrollView>

      {/* Bottom Submit Bar */}
      <View style={styles.bottomBar}>
        <View style={styles.bottomSummary}>
          <Text style={styles.summaryText}>
            {files.length > 0 ? `${files.length} tệp` : ''}
            {files.length > 0 && textContent.trim() ? ' • ' : ''}
            {textContent.trim() ? `${textContent.length} ký tự` : ''}
            {files.length === 0 && !textContent.trim() ? 'Chưa có nội dung' : ''}
          </Text>
        </View>
        <TouchableOpacity
          style={[
            styles.submitBtn,
            submitting && styles.submitBtnDisabled,
          ]}
          disabled={submitting}
          onPress={handleSubmit}>
          {submitting ? (
            <ActivityIndicator size="small" color="#FFFFFF" />
          ) : (
            <>
              <Text style={styles.submitBtnIcon}>📤</Text>
              <Text style={styles.submitBtnText}>Nộp bài</Text>
            </>
          )}
        </TouchableOpacity>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F0F4F8',
  },
  header: {
    backgroundColor: '#0D47A1',
    paddingTop: (StatusBar.currentHeight || 0) + 12,
    paddingBottom: 16,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 6,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  backIcon: {
    fontSize: 20,
    color: '#FFFFFF',
    fontWeight: 'bold',
  },
  headerContent: {
    flex: 1,
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  headerSubtitle: {
    fontSize: 13,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 2,
  },
  content: {
    flex: 1,
    padding: 16,
  },
  infoBanner: {
    flexDirection: 'row',
    backgroundColor: '#E3F2FD',
    borderRadius: 6,
    padding: 12,
    marginBottom: 16,
    borderLeftWidth: 3,
    borderLeftColor: '#0D47A1',
  },
  infoBannerIcon: {
    fontSize: 20,
    marginRight: 10,
    marginTop: 2,
  },
  infoBannerContent: {
    flex: 1,
  },
  infoBannerTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#0D47A1',
    marginBottom: 4,
  },
  infoBannerText: {
    fontSize: 13,
    color: '#1565C0',
    lineHeight: 20,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#0D47A1',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.06,
    shadowRadius: 3,
    elevation: 2,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#0F172A',
    marginBottom: 4,
  },
  cardSubtitle: {
    fontSize: 13,
    color: '#94A3B8',
    marginBottom: 12,
  },
  filesList: {
    marginBottom: 12,
  },
  selectedFile: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 10,
    backgroundColor: '#F0F4F8',
    borderRadius: 6,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  selectedFileIcon: {
    fontSize: 24,
    marginRight: 10,
  },
  selectedFileInfo: {
    flex: 1,
  },
  selectedFileName: {
    fontSize: 13,
    fontWeight: '500',
    color: '#334155',
  },
  selectedFileSize: {
    fontSize: 11,
    color: '#94A3B8',
    marginTop: 2,
  },
  removeFileButton: {
    width: 28,
    height: 28,
    borderRadius: 6,
    backgroundColor: '#FEE2E2',
    justifyContent: 'center',
    alignItems: 'center',
  },
  removeFileIcon: {
    fontSize: 12,
    color: '#EF4444',
    fontWeight: 'bold',
  },
  addFileButtons: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  addFileButton: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 14,
    marginHorizontal: 4,
    backgroundColor: '#F0F4F8',
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderStyle: 'dashed',
  },
  addFileButtonIcon: {
    fontSize: 24,
    marginBottom: 4,
  },
  addFileButtonText: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '500',
  },
  textInput: {
    backgroundColor: '#F0F4F8',
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    padding: 12,
    fontSize: 14,
    color: '#334155',
    minHeight: 200,
    lineHeight: 22,
  },
  charCount: {
    fontSize: 12,
    color: '#94A3B8',
    textAlign: 'right',
    marginTop: 6,
  },
  bottomSpace: {
    height: 100,
  },
  // Bottom bar
  bottomBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#FFFFFF',
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    ...Platform.select({
      ios: {paddingBottom: 28},
      android: {paddingBottom: 12},
    }),
  },
  bottomSummary: {
    flex: 1,
    marginRight: 12,
  },
  summaryText: {
    fontSize: 13,
    color: '#64748B',
  },
  submitBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#0D47A1',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 6,
  },
  submitBtnDisabled: {
    opacity: 0.6,
  },
  submitBtnIcon: {
    fontSize: 16,
    marginRight: 6,
  },
  submitBtnText: {
    fontSize: 15,
    fontWeight: '700',
    color: '#FFFFFF',
  },
});

export default AssignmentSubmitScreen;
